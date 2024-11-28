<?php
namespace Database\Seeders;

use App\Models\PaymentCallback;
use App\Models\Payment;
use Illuminate\Database\Seeder;

class PaymentCallbackSeeder extends Seeder
{
    public function run()
    {
        $payments = Payment::all();

        foreach ($payments as $payment) {
            PaymentCallback::create([
                'payment_id' => $payment->id,
                'callback_type' => 'simba_notification',
                'payload' => [
                    'status' => 'success',
                    'transaction_id' => $payment->transaction_id,
                    'amount' => $payment->amount,
                    'currency' => $payment->currency,
                    'timestamp' => now()->toISOString()
                ],
                'status' => 'success'
            ]);
        }
    }
}
