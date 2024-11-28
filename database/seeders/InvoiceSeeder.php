<?php
// database/seeders/InvoiceSeeder.php
namespace Database\Seeders;

use App\Models\Invoice;
use App\Models\Merchant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class InvoiceSeeder extends Seeder
{
    public function run()
    {
        $merchants = Merchant::all();

        foreach ($merchants as $merchant) {
            for ($i = 0; $i < 5; $i++) {
                Invoice::create([
                    'merchant_id' => $merchant->id,
                    'invoice_number' => 'INV-' . Str::random(10),
                    'hms_reference' => 'HMS-' . Str::random(8),
                    'customer_id' => 'CUST-' . Str::random(6),
                    'customer_name' => 'Test Customer ' . ($i + 1),
                    'customer_phone' => '+255' . rand(100000000, 999999999),
                    'customer_email' => 'customer' . ($i + 1) . '@example.com',
                    'amount' => rand(1000, 100000),
                    'currency' => 'TZS',
                    'description' => 'Test Invoice ' . ($i + 1),
                    'status' => 'pending',
                    'expires_at' => now()->addDays(1)
                ]);
            }
        }
    }
}
