<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('CREATE INDEX IF NOT EXISTS idx_company_requests_status_type ON company_requests(request_status, foreign_local);');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_companies_sector_id_active ON companies(sector_id, is_active);');
        DB::statement("
        CREATE MATERIALIZED VIEW IF NOT EXISTS mv_tickets_analytics AS
        SELECT
            -- أ. إجمالي عدد التذاكر المحجوزة/المباعة بالكامل (تعتمد على created_at كالمعتاد)
            COUNT(id) as total_tickets,

            -- ب. 🔥 التحديث: متوسط معدل مسح الـ QR (الدخول) في الساعة بناءً على used_at
            COALESCE(
                (
                    SELECT AVG(hourly_count)
                    FROM (
                        SELECT COUNT(id) as hourly_count
                        FROM tickets
                        WHERE status = 'used' AND used_at IS NOT NULL
                        GROUP BY DATE_TRUNC('hour', used_at)
                    ) sub_hourly
                ),
                0
            ) as avg_hourly_rate,

            -- ج. 🔥 التحديث: الساعة الأكثر ازدحاماً بالدخول الفعلي بناءً على used_at
            COALESCE(
                (
                    SELECT EXTRACT(HOUR FROM used_at)
                    FROM tickets
                    WHERE status = 'used' AND used_at IS NOT NULL
                    GROUP BY EXTRACT(HOUR FROM used_at)
                    ORDER BY COUNT(id) DESC
                    LIMIT 1
                ),
                9
            ) as peak_hour,

            -- د. 🔥 التحديث: توزيع الدخول الفعلي على مدار الأيام السبعة بناءً على used_at للـ Bar Chart
            COALESCE(
                (
                    SELECT json_agg(t) FROM (
                        SELECT
                            EXTRACT(DOW FROM used_at) as day_index,
                            COUNT(id) as tickets_count
                        FROM tickets
                        WHERE status = 'used' AND used_at IS NOT NULL
                        GROUP BY EXTRACT(DOW FROM used_at)
                        ORDER BY day_index ASC
                    ) t
                ),
                '[]'::json
            ) as weekly_distribution
        FROM tickets;
    ");
    }
    public function down(): void
    {
        DB::statement('DROP MATERIALIZED VIEW IF EXISTS mv_tickets_analytics;');
    }

};
