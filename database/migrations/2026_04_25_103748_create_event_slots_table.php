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
        Schema::create('event_slots', function (Blueprint $table) {
            $table->id();
            $table->date('slot_date')->index();
            $table->time('start_time');
            $table->time('end_time');
            $table->boolean('available')->default('true');
            $table->timestamps();

            //$table->index('available');
            $table->index(['slot_date', 'available']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('event_slots', function (Blueprint $table) {
            $table->dropIndex(['slot_date', 'available']);
        });    }
};
