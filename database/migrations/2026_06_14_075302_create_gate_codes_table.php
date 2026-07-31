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
        Schema::create('gate_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique();
            $table->date('valid_for_date')->index(); // صالح ليوم محدد فقط
            $table->time('starts_at'); // بداية ساعات المعرض
            $table->time('ends_at'); // نهاية ساعات المعرض
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gate_codes');
    }
};
