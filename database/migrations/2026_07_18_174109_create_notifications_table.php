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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('body')->nullable();
            $table->enum('type', ['company_request', 'event_request', 'visitor_complaint', 'company_complaint', 'system_error']);
            $table->enum('status', ['unread', 'read', 'archived'])->default('unread');
            $table->enum('sender', ['visitor', 'company', 'system'])->nullable();
            $table->json('data')->nullable(); // لتخزين أي IDs مرتبطة بالإشعار ديناميكياً
            $table->timestamps();

            // فهارس (Indexes) لضمان سرعة بحث وتصفية هائلة (High Performance)
            $table->index(['status', 'type', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
