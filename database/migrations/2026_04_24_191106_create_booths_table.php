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
        Schema::create('booths', function (Blueprint $table) {
            $table->id();

            $table->string('booth_number');
            $table->enum('booth_type',['sales','display']);
            $table->enum('equipment_type',['Equipped Booth',
                'Not Equipped Booth',
                'Row Space Only',
                'Kiosk AB',
                'Kiosk CD']);
            $table->float('size_sqm');
            $table->boolean('available')->default('true');
            $table->foreignId('sector_id')->constrained('sectors')->onDelete('cascade');
            $table->foreignId('company_id')->nullable()->constrained('companies')->onDelete('set null');
            $table->foreignId('hall_id')->nullable()->constrained('halls')->onDelete('cascade');
            $table->foreignId('company_request_id')->nullable()->constrained('company_requests')->onDelete('set null');

            $table->timestamps();

            //$table->index('booth_number');
            //$table->index('available');
            $table->index('company_id');
            $table->index(['sector_id', 'available']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('booths', function (Blueprint $table) {
            $table->dropIndex(['company_id']);
            $table->dropIndex(['hall_id', 'available']);
        });    }
};
