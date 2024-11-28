<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Services\QRCodeService;
use Illuminate\Http\Request;
use App\Models\Invoice;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class InvoiceController extends Controller
{
    protected $qrCodeService;

    public function __construct(QRCodeService $qrCodeService)
    {
        $this->qrCodeService = $qrCodeService;
    }

    public function store(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            // Validate request
            $validated = $request->validate([
                'merchant_id' => 'required|string',
                'amount' => 'required|numeric|min:0',
                'currency' => 'required|string|size:3',
                'callback_url' => 'required|url',
                'metadata' => 'sometimes|array'
            ]);

            // Create invoice
            $invoice = Invoice::create([
                'external_id' => Str::uuid(),
                'merchant_id' => $validated['merchant_id'],
                'amount' => $validated['amount'],
                'currency' => $validated['currency'],
                'status' => 'PENDING',
                'callback_url' => $validated['callback_url'],
                'metadata' => $validated['metadata'] ?? null,
            ]);

            // Generate QR code
            $qrCode = $this->qrCodeService->generateForInvoice($invoice);

            return response()->json([
                'status' => 'success',
                'data' => [
                    'invoice' => [
                        'external_id' => $invoice->external_id,
                        'control_number' => $qrCode->control_number,
                        'amount' => [
                            'value' => $invoice->amount,
                            'currency' => $invoice->currency,
                            'formatted' => number_format($invoice->amount, 2) . ' ' . $invoice->currency
                        ],
                        'status' => $invoice->status,
                        'merchant' => [
                            'id' => $invoice->merchant_id,
                            'callback_url' => $invoice->callback_url
                        ],
                        'timestamps' => [
                            'created_at' => $invoice->created_at,
                            'expires_at' => $qrCode->expires_at,
                            'expires_in_seconds' => now()->diffInSeconds($qrCode->expires_at)
                        ]
                    ],
                    'payment' => [
                        'methods' => ['simba_money'],
                        'qr_code' => [
                            'data' => base64_encode($qrCode->qr_data),
                            'mime_type' => 'image/svg+xml',
                            'control_number' => $qrCode->control_number,
                            'expires_at' => $qrCode->expires_at
                        ],
                        'instructions' => [
                            'en' => sprintf(
                                'Scan this QR code or enter control number %s to pay %s %s',
                                $qrCode->control_number,
                                number_format($invoice->amount, 2),
                                $invoice->currency
                            ),
                            'sw' => sprintf(
                                'Scan QR code au ingiza namba %s kulipa %s %s',
                                $qrCode->control_number,
                                number_format($invoice->amount, 2),
                                $invoice->currency
                            )
                        ]
                    ]
                ]
            ], 201);

        } catch (\Exception $e) {
            Log::error('Invoice creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create invoice',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
