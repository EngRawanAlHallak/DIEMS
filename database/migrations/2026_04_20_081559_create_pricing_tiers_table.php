<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pricing_tiers', function (Blueprint $table) {
            $table->id();

            // ─── وصف السعر ────────────────────────────────
            $table->string('name');
            // مثال: "مبنى مجهز - وطنية"، "أكشاك مركز A/B"

            $table->string('slug');
            // مثال: furnished_local, kiosk_ab
            // للاستخدام في الكود بدل الـ ID

            // ─── على من ينطبق ─────────────────────────────
            $table->enum('company_type', ['local', 'foreign', 'any'])
                ->default('any');
            // any = ينطبق على الكل (ألعاب، قاعات، رجال أعمال)

            // ─── نوع التسعير — القلب ──────────────────────
            $table->enum('pricing_type', [
                'per_sqm',        // سعر × مساحة  (مباني، مكشوف، ألعاب)
                'per_day',        // سعر × عدد أيام (رجال أعمال، قاعات)
                'flat_per_period', // مبلغ ثابت لفترة محددة (أكشاك)
            ]);

            // ─── السعر الأساسي ────────────────────────────
            $table->decimal('unit_price', 10, 2);
            // per_sqm  → سعر المتر المربع
            // per_day  → سعر اليوم
            // flat     → المبلغ الثابت للفترة

            $table->string('currency', 3)->default('USD');

            // ─── للـ flat_per_period فقط ─────────────────
            $table->unsignedInteger('period_days')->nullable();
            // الأكشاك = 10 أيام

            // ─── للـ per_sqm فقط ──────────────────────────
            $table->decimal('min_area', 8, 2)->nullable();
            // مبنى: 12 متر كحد أدنى
            // مكشوف: 50 متر كحد أدنى
            // ألعاب: null (لا يوجد حد أدنى)

            // ─── تصنيف المنطقة (للأكشاك) ─────────────────
            $table->string('location_zone')->nullable();
            // 'ab' للمراكز A,B || 'cd' للمراكز C,D || null للباقي

            // ─── تفاصيل إضافية للعرض ─────────────────────
            $table->text('description')->nullable();
            // وصف للفورم: "مبنى مجهز بالكامل بما يشمل..."

            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pricing_tiers');
    }
};
