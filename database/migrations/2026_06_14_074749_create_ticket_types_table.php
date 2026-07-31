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
        Schema::create('ticket_types', function (Blueprint $table) {
            $table->id();
            $table->jsonb('name'); // اسم التذكرة (عادي، VIP، مجموعة)
            $table->jsonb('description')->nullable();
            $table->decimal('price')->default(0);
            $table->integer('persons_count')->default(1); // عدد الأفراد للتذكرة الواحدة
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_types');
    }
};
