<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\Merchant;
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
            // Start transaction
            $result = DB::transaction(function () use ($request) {
                $validated = $request->validated();

                // Find merchant first
                $merchant = Merchant::where('merchant_code', $validated['merchant_id'])
                    ->where('status', 'ACTIVE')
                    ->first();

                if (!$merchant) {
                    throw new \Exception('Invalid or inactive merchant', 404);
                }

                // Create invoice with merchant's actual ID
                $invoice = Invoice::create([
                    'external_id' => Str::uuid(),
                    'merchant_id' => $merchant->id,
                    'payer_name' => $validated['payer_name'],
                    'invoice_number' => $this->generateInvoiceNumber($merchant),
                    'service_code' => $validated['service_code'],
                    'bill_amount' => $validated['bill_amount'],
                    'currency_code' => strtoupper($validated['currency_code']),
                    'bank_name' => $validated['bank_name'] ?? null,
                    'bank_account' => $validated['bank_account'] ?? null,
                    'status' => Invoice::STATUS_PENDING,
                    'callback_url' => $merchant->callback_url,
                    'metadata' => $validated['metadata'] ?? null,
                ]);



                // Generate QR code
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
                'code' => $e->getCode(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->except(['password', 'token'])
            ]);

            $statusCode = $e->getCode() === 404 ? 404 : 500;

            return response()->json([
                'status' => 'error',
                'message' => config('app.debug')
                    ? $e->getMessage()
                    : ($statusCode === 404
                        ? 'Invalid merchant code provided'
                        : 'Failed to create invoice. Please try again later.'),
                'code' => $statusCode === 404 ? 'MERCHANT_NOT_FOUND' : 'INVOICE_CREATION_FAILED'
            ], $statusCode);
        }
    }

    /**
     * Generate unique invoice number
     */
    private function generateInvoiceNumber(Merchant $merchant): string
    {
        return sprintf(
            '%s-%s-%s',
            $merchant->merchant_code,
            now()->format('Ymd'),
            strtoupper(Str::random(5))
        );
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
                'bank_details' => [
                    'bank_name' => $invoice->bank_name,
                    'bank_account' => $invoice->bank_account
                ],
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
                'Scan this QR code or enter control number %s to pay %s. You can also transfer to bank account %s at %s',
                $controlNumber,
                $amount,
                $invoice->bank_account ?? 'N/A',
                $invoice->bank_name ?? 'N/A'
            ),
            'sw' => sprintf(
                'Scan QR code au ingiza namba %s kulipa %s. Unaweza pia kutuma kwenye akaunti ya benki %s katika %s',
                $controlNumber,
                $amount,
                $invoice->bank_account ?? 'N/A',
                $invoice->bank_name ?? 'N/A'
            )
        ];
    }
}
