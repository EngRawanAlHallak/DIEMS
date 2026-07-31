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
        Schema::create('company_requests', function (Blueprint $table) {
            $table->id();
            $table->enum('foreign_local' , ['foreign', 'local',])->default('local');
            $table->jsonb('company_name');
            $table->string('responsible_name');
            $table->string('job_title');
            $table->string('email')->unique();
            $table->string('phone');
            $table->jsonb('nationality');
            $table->string('commercial_register');
            $table->jsonb('address');
            $table->string('sector');
            $table->jsonb('company_description');
            $table->foreignId('company_id')->nullable()->constrained('companies')->onDelete('restrict');

            // بيانات المساحة والبثوث
            $table->float('requested_area'); // المساحة بالمتر المربع

            $table->enum('setup_preference', [
                'Equipped Booth',
                'Not Equipped Booth',
                'Row Space Only',
                'Kiosk AB',
                'Kiosk CD'
            ]);

            // الشروط
            $table->timestamp('terms_accepted_at')->nullable();

            // حالة الطلب
            $table->enum('request_status', [
                'pending',
                'approved',
                'rejected',
                'action_required',
                'expired'
            ])->default('pending');

            // حالة الدفع
            $table->enum('payment_status', [
                'paid',
                'unpaid',
                'partial_paid'
            ])->default('unpaid');

            // تفاصيل الدفع
            $table->decimal('total_price', 10, 2)->nullable();
            $table->decimal('required_deposit', 10, 2)->nullable();
            $table->decimal('paid_amount', 10, 2)->default(0);
            $table->date('payment_due_date')->nullable();
            $table->string('admin_notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_requests');
    }
};
