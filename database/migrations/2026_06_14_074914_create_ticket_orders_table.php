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
        Schema::create('ticket_orders', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('guest_id')->index(); // المعرف القادم من Local Storage
            $table->decimal('total_amount');
            $table->enum('payment_status', ['pending', 'paid', 'failed'])->default('pending')->index();
            $table->timestamp('expires_at')->nullable(); // وقت انتهاء الحجز إذا لم يدفع
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_orders');
    }
};
