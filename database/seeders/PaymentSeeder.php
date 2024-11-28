<?php

namespace Database\Seeders;

use App\Models\Payment;
use App\Models\Invoice;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PaymentSeeder extends Seeder
{
    public function run()
    {
        $invoices = Invoice::all();

        foreach ($invoices as $invoice) {
            Payment::create([
                'invoice_id' => $invoice->id,
                'transaction_id' => 'TXN-' . Str::random(10),
                'simba_reference' => 'SIMBA-' . Str::random(8),
                'amount' => $invoice->amount,
                'currency' => $invoice->currency,
                'status' => 'completed',
                'payment_method' => 'simba_money',
                'payment_details' => [
                    'payment_time' => now()->toISOString(),
                    'customer_phone' => $invoice->customer_phone
                ]
            ]);
        }
    }
}
