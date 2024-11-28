<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Transaction;
use App\Models\QRCode;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class PaymentService
{
    protected $qrCodeService;
    protected $notificationService;

    public function __construct(
        QRCodeService $qrCodeService,
        NotificationService $notificationService
    ) {
        $this->qrCodeService = $qrCodeService;
        $this->notificationService = $notificationService;
    }

    public function createInvoice(array $data): Invoice
    {
        $invoice = Invoice::create([
            'external_id' => Str::uuid(),
            'merchant_id' => $data['merchant_id'],
            'amount' => $data['amount'],
            'currency' => $data['currency'],
            'status' => 'PENDING',
            'callback_url' => $data['callback_url'],
            'metadata' => $data['metadata'] ?? null,
        ]);

        // Generate QR code for the invoice
        $this->qrCodeService->generateForInvoice($invoice);

        return $invoice->fresh();
    }

    public function initiatePayment(Invoice $invoice, array $paymentData): Transaction
    {
        $transaction = Transaction::create([
            'transaction_id' => Str::uuid(),
            'invoice_id' => $invoice->id,
            'payment_provider' => $paymentData['provider'],
            'payment_method' => $paymentData['method'],
            'amount' => $invoice->amount,
            'currency' => $invoice->currency,
            'status' => 'INITIATED'
        ]);

        // Update invoice status
        $invoice->update(['status' => 'PROCESSING']);

        return $transaction;
    }

    public function confirmPayment(Transaction $transaction, array $confirmationData): bool
    {
        $transaction->update([
            'status' => 'CONFIRMED',
            'provider_response' => $confirmationData,
            'processed_at' => now()
        ]);

        $invoice = $transaction->invoice;
        $invoice->update(['status' => 'PAID']);

        // Notify merchant via callback
        $this->notificationService->sendCallback($invoice, [
            'status' => 'PAID',
            'transaction_id' => $transaction->transaction_id,
            'payment_provider' => $transaction->payment_provider,
            'processed_at' => $transaction->processed_at
        ]);

        return true;
    }

    public function handleSimbaWebhook(array $webhookData): void
    {
        // Verify webhook signature
        $this->verifyWebhookSignature($webhookData);

        $transaction = Transaction::where('transaction_id', $webhookData['transaction_id'])->firstOrFail();

        if ($webhookData['status'] === 'SUCCESS') {
            $this->confirmPayment($transaction, $webhookData);
        } else {
            $this->handleFailedPayment($transaction, $webhookData);
        }
    }

    protected function handleFailedPayment(Transaction $transaction, array $failureData): void
    {
        $transaction->update([
            'status' => 'FAILED',
            'provider_response' => $failureData,
            'processed_at' => now()
        ]);

        $invoice = $transaction->invoice;
        $invoice->update(['status' => 'FAILED']);

        $this->notificationService->sendCallback($invoice, [
            'status' => 'FAILED',
            'transaction_id' => $transaction->transaction_id,
            'error' => $failureData['error'] ?? 'Payment failed',
            'processed_at' => now()
        ]);
    }

    protected function verifyWebhookSignature(array $webhookData): bool
    {
        // Implement Simba Money webhook signature verification
        // This is a placeholder - implement actual verification logic
        return true;
    }
}
