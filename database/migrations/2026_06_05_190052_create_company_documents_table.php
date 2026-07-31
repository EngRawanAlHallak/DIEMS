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
        Schema::create('company_documents', function (Blueprint $table) {
            $table->id();
            $table->morphs('documentable');//هاي مشان نفس الملفات وقت تنقبل الشركة غيرها للتصير للشركة بدون ما كرر
            $table->string('file_path');
            $table->string('file_type'); // 'commercial_register', 'contract', 'identity', etc.
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_documents');
    }
};
