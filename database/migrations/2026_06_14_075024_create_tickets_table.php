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
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique(); // هذا الـ UUID هو الذي سيتحول لـ QR Code
            $table->foreignId('ticket_order_id')->constrained('ticket_orders')->OnDelete('set null');
            $table->foreignId('ticket_type_id')->constrained('ticket_types')->OnDelete('set null');
            $table->string('visitor_name'); // اسم صاحب التذكرة (لأن الطلب قد يحتوي عدة أشخاص)
            $table->string('visitor_email');
            $table->string('visitor_phone');
            $table->string('interest_field'); // مجال الاهتمام
            $table->enum('status', ['valid', 'used', 'cancelled'])->default('valid')->index();
            $table->timestamp('used_at')->nullable(); // متى تم عمل Scan للتذكرة
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
