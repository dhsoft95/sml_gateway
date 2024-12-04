<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('merchants', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('business_name');
            $table->string('merchant_code')->unique();
            $table->string('callback_url')->nullable();
            $table->string('notification_email');
            $table->string('webhook_secret')->nullable();
            $table->string('api_key')->nullable();
            $table->timestamp('api_key_generated_at')->nullable();
            $table->enum('status', ['ACTIVE', 'SUSPENDED', 'INACTIVE'])->default('INACTIVE');
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('merchants');
    }
};
