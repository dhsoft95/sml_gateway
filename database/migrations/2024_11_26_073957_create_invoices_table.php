<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInvoicesTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->uuid('external_id')->unique();
            $table->string('merchant_id');
            $table->string('payer_name');
            $table->string('service_code');
            $table->string('invoice_number')->unique();
            $table->decimal('bill_amount', 10, 2);
            $table->string('currency_code', 3);
            $table->string('status');
            $table->string('callback_url');
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
}
