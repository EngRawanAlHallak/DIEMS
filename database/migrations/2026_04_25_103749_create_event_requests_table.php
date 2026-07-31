<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('event_requests', function (Blueprint $table) {
            $table->id();

            $table->foreignId('slot_id')->constrained('event_slots')->onDelete('cascade');
            $table->foreignId('sector_id')->constrained('sectors');
            $table->foreignId('hall_id')->nullable()->constrained('halls')->onDelete('set null');
            $table->string('organizer_name');
            $table->string('organizer_email');
            $table->string('organizer_phone');

            $table->jsonb('event_title');
            $table->jsonb('event_description');
            $table->integer('Expected_attendance');
            $table->text('equipment_needed')->nullable();
            $table->string('image')->nullable(); //مسار صورة الفعالية
            $table->boolean('is_special')->default(false);

            $table->enum('request_status', ['pending', 'approved', 'rejected', 'cancelled', 'expired'
            ])->default('pending');

            // حالة الدفع
            $table->enum('payment_status', ['paid', 'unpaid',])->default('unpaid');

            // تفاصيل الدفع
            $table->decimal('total_price', 10, 2)->nullable();
            //$table->decimal('required_deposit', 10, 2)->nullable();
            //$table->decimal('paid_amount', 10, 2)->default(0);
            $table->date('payment_due_date')->nullable();
            $table->timestamps();

            $table->index('payment_status'); // لتقارير المالية
            $table->index(['request_status', 'payment_status']); // لاستعلامات "المقبول ولم يدفع"
            $table->index(['sector_id', 'request_status']); // لفلترة القطاعات في لوحة التحكم
        });

        DB::statement('CREATE UNIQUE INDEX unique_approved_slot ON event_requests (slot_id) WHERE request_status = \'approved\'');

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('event_requests', function (Blueprint $table) {
            $table->dropIndex('payment_status');
            $table->dropIndex(['request_status', 'payment_status']);
            $table->dropIndex(['sector_id', 'request_status']);
        });
        DB::statement('DROP INDEX unique_approved_slot');

    }
};
