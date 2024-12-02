<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Services\QRCodeService;
use Illuminate\Http\Request;
use App\Models\Invoice;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Http\Resources\InvoiceResource;
use Illuminate\Http\JsonResponse;
use App\Http\Requests\StoreInvoiceRequest;
use Illuminate\Support\Facades\DB;

class InvoiceController extends Controller
{
    protected QRCodeService $qrCodeService;

    public function __construct(QRCodeService $qrCodeService)
    {
        $this->qrCodeService = $qrCodeService;
    }

    /**
     * Store a new invoice and generate QR code
     *
     * @param StoreInvoiceRequest $request
     * @return JsonResponse
     */
    public function store(StoreInvoiceRequest $request): JsonResponse
    {
        try {
            $result = DB::transaction(function () use ($request) {
                $validated = $request->validated();

                $invoice = Invoice::create([
                    'external_id' => Str::uuid(),
                    'merchant_id' => $validated['merchant_id'],
                    'payer_name' => $validated['payer_name'],
                    'invoice_number' => $validated['invoice_number'],
                    'service_code' => $validated['service_code'],
                    'bill_amount' => $validated['bill_amount'],
                    'currency_code' => strtoupper($validated['currency_code']),
                    'status' => Invoice::STATUS_PENDING,
                    'callback_url' => $validated['callback_url'],
                    'metadata' => $validated['metadata'] ?? null,
                ]);

                $qrCode = $this->qrCodeService->generateForInvoice($invoice);

                return $this->formatResponse($invoice, $qrCode);
            });

            return response()->json([
                'status' => 'success',
                'data' => $result
            ], 201);

        } catch (\Exception $e) {
            Log::error('Invoice creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->except(['password', 'token'])
            ]);

            return response()->json([
                'status' => 'error',
                'message' => config('app.debug')
                    ? $e->getMessage()
                    : 'Failed to create invoice. Please try again later.',
                'code' => 'INVOICE_CREATION_FAILED'
            ], 500);
        }
    }

    /**
     * Format the response data
     *
     * @param Invoice $invoice
     * @param object $qrCode
     * @return array
     */
    private function formatResponse(Invoice $invoice, object $qrCode): array
    {
        return [
            'invoice' => [
                'external_id' => $invoice->external_id,
                'control_number' => $qrCode->control_number,
                'amount' => [
                    'value' => $invoice->bill_amount,
                    'currency' => $invoice->currency_code,
                    'formatted' => $this->formatAmount($invoice->bill_amount, $invoice->currency_code)
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
                'methods' => config('payment.available_methods', ['simba_money']),
                'qr_code' => [
                    'data' => base64_encode($qrCode->qr_data),
                    'mime_type' => 'image/svg+xml',
                    'control_number' => $qrCode->control_number,
                    'expires_at' => $qrCode->expires_at
                ],
                'instructions' => $this->getPaymentInstructions($qrCode->control_number, $invoice)
            ]
        ];
    }

    /**
     * Format amount with currency
     *
     * @param float $amount
     * @param string $currency
     * @return string
     */
    private function formatAmount(float $amount, string $currency): string
    {
        return sprintf(
            '%s %s',
            number_format($amount, 2),
            $currency
        );
    }

    /**
     * Get payment instructions in multiple languages
     *
     * @param string $controlNumber
     * @param Invoice $invoice
     * @return array
     */
    private function getPaymentInstructions(string $controlNumber, Invoice $invoice): array
    {
        $amount = $this->formatAmount($invoice->bill_amount, $invoice->currency_code);

        return [
            'en' => sprintf(
                'Scan this QR code or enter control number %s to pay %s',
                $controlNumber,
                $amount
            ),
            'sw' => sprintf(
                'Scan QR code au ingiza namba %s kulipa %s',
                $controlNumber,
                $amount
            )
        ];
    }
}
