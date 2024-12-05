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
            $table->unsignedBigInteger('merchant_id');
            $table->string('payer_name');
            $table->string('invoice_number');
            $table->string('service_code');
            $table->decimal('bill_amount', 10, 2);
            $table->string('currency_code', 3);
            $table->string('status');
            $table->string('bank_name')->nullable();
            $table->string('bank_account')->nullable();
            $table->string('callback_url');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['merchant_id', 'status']);
            $table->index(['created_at', 'status']);
            $table->index(['bank_name', 'bank_account']);
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
