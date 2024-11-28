<?php
namespace Database\Seeders;

use App\Models\Merchant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class MerchantSeeder extends Seeder
{
    public function run()
    {
        Merchant::create([
            'name' => 'Test Hospital',
            'merchant_id' => 'HOSP' . Str::random(8),
            'api_key' => Str::random(32),
            'webhook_url' => 'https://test-hospital.com/api/webhooks',
            'is_active' => true,
            'settings' => [
                'notification_email' => 'admin@test-hospital.com',
                'auto_reconciliation' => true
            ]
        ]);

        Merchant::create([
            'name' => 'Demo Clinic',
            'merchant_id' => 'CLIN' . Str::random(8),
            'api_key' => Str::random(32),
            'webhook_url' => 'https://demo-clinic.com/api/webhooks',
            'is_active' => true
        ]);
    }
}
