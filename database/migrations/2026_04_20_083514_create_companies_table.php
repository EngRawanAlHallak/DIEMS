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
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('user_id')->constrained()->onDelete('cascade');
            //$table->foreignId('company_request_id')->constrained()->onDelete('restrict');
            $table->jsonb('name');
            $table->string('logo')->nullable();
            $table->string('responsible_person');
            $table->string('sector');
            $table->foreignId('sector_id')->constrained('sectors')->onDelete('cascade');
            $table->jsonb('bio')->nullable();
            $table->jsonb('nationality');
            $table->jsonb('address');
            $table->float('final_area');
            $table->string('booth_type'); // 'Equipped Booth', etc.
            $table->boolean('is_active')->default(true);

            $table->softDeletes();
            $table->timestamps();
            $table->index(['sector', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropIndex(['sector', 'is_active']);
        });
    }
};
