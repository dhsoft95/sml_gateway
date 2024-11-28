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
        Schema::create('failed_callbacks', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_id');
            $table->string('callback_url');
            $table->json('payload');
            $table->integer('attempts')->default(0);
            $table->timestamp('next_retry_at');
            $table->string('status');
            $table->text('last_error')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('failed_callbacks');
    }
};
