<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $this->call([
            MerchantSeeder::class,
            InvoiceSeeder::class,
            PaymentSeeder::class,
            PaymentCallbackSeeder::class,
            WebhookLogSeeder::class,
            TransactionSeeder::class,
        ]);
    }
}
