<?php

namespace Tests\Feature\Admin;

use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminApiTest extends TestCase
{
    public function test_admin_routes_require_authentication(): void
    {
        $response = $this->getJson('/api/admin/company-requests');

        $response->assertStatus(401);
    }

    public function test_company_user_cannot_access_admin_routes(): void
    {
        $user = $this->createCompanyUser();
        Sanctum::actingAs($user, ['company']);

        $response = $this->getJson('/api/admin/company-requests');

        $response->assertStatus(403);
    }

    public function test_admin_can_list_company_requests(): void
    {
        $admin = $this->createAdmin();
        Sanctum::actingAs($admin, ['admin']);

        $response = $this->getJson('/api/admin/company-requests');

        $this->assertApiSuccess($response);
    }

    public function test_admin_can_get_sectors(): void
    {
        $admin = $this->createAdmin();
        Sanctum::actingAs($admin, ['admin']);

        $response = $this->getJson('/api/admin/sectors');

        $this->assertApiSuccess($response);
    }

    public function test_admin_can_get_halls(): void
    {
        $admin = $this->createAdmin();
        Sanctum::actingAs($admin, ['admin']);

        $response = $this->getJson('/api/admin/halls');

        $this->assertApiSuccess($response);
    }

    public function test_admin_can_get_dashboard_statistics(): void
    {
        $admin = $this->createAdmin();
        Sanctum::actingAs($admin, ['admin']);

        $response = $this->getJson('/api/admin/dashboard/statistics');

        $this->assertApiSuccess($response);
    }

    public function test_admin_can_get_notifications(): void
    {
        $admin = $this->createAdmin();
        Sanctum::actingAs($admin, ['admin']);

        $response = $this->getJson('/api/admin/notifications');

        $this->assertApiSuccess($response);
    }

    public function test_admin_can_get_ticket_types(): void
    {
        $admin = $this->createAdmin();
        Sanctum::actingAs($admin, ['admin']);

        $response = $this->getJson('/api/admin/ticket/get-types');

        $this->assertApiSuccess($response);
    }

    public function test_admin_can_get_ticket_metrics(): void
    {
        $admin = $this->createAdmin();
        Sanctum::actingAs($admin, ['admin']);

        $response = $this->getJson('/api/admin/ticket/metrics');

        $this->assertApiSuccess($response);
    }

    public function test_admin_can_get_joined_companies(): void
    {
        $admin = $this->createAdmin();
        Sanctum::actingAs($admin, ['admin']);

        $response = $this->getJson('/api/admin/joined-companies');

        $this->assertApiSuccess($response);
    }

    public function test_admin_can_get_event_requests(): void
    {
        $admin = $this->createAdmin();
        Sanctum::actingAs($admin, ['admin']);

        $response = $this->getJson('/api/admin/event-requests');

        $this->assertApiSuccess($response);
    }

    public function test_admin_can_get_activity_logs_with_filters(): void
    {
        $admin = $this->createAdmin();
        Sanctum::actingAs($admin, ['admin']);

        $response = $this->postJson('/api/admin/activity-logs', [
            'page' => 1,
            'per_page' => 10,
        ]);

        $this->assertApiSuccess($response);
    }

    public function test_admin_can_get_content_transportation(): void
    {
        $admin = $this->createAdmin();
        Sanctum::actingAs($admin, ['admin']);

        $response = $this->getJson('/api/admin/content/transportation');

        $this->assertApiSuccess($response);
    }

    public function test_admin_can_get_backup_files_list(): void
    {
        $admin = $this->createAdmin();
        Sanctum::actingAs($admin, ['admin']);

        $response = $this->getJson('/api/admin/database/backup/files');

        $this->assertApiSuccess($response);
    }

    public function test_admin_add_sector_requires_name(): void
    {
        $admin = $this->createAdmin();
        Sanctum::actingAs($admin, ['admin']);

        $response = $this->postJson('/api/admin/sectors/add', []);

        $this->assertApiValidationError($response);
    }
}
