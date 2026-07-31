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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique(); // للتعامل الآمن مع الفرونت إند

            // ربط متعدد الأشكال (تحديد هل الدفع تابع لـ TicketOrder أم CompanyRequest)
            $table->morphs('payable');

            $table->string('paymera_payment_id')->nullable()->index(); // ID العائد من Paymera
            $table->decimal('amount', 12, 2);
            $table->string('currency', 10)->default('SAR');

            // حالة الدفع الداخلية في نظامنا
            $table->enum('status', ['pending', 'paid', 'failed', 'cancelled', 'expired'])->default('pending');

            $table->text('payment_url')->nullable(); // رابط صفحة الدفع المولّد من Paymera
            $table->timestamp('expires_at')->nullable(); // تاريخ انتهاء صلاحية رابط الدفع

            $table->json('gateway_response')->nullable(); // لتخزين رد Paymera بالكامل للتدقيق (Audit Log)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
