<?php

namespace Tests\Feature\Company;

use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CompanyApiTest extends TestCase
{
    public function test_public_company_initial_page_is_accessible(): void
    {
        $response = $this->getJson('/api/company/initial-page');

        $this->assertApiSuccess($response);
    }

    public function test_public_company_forms_info_is_accessible(): void
    {
        $response = $this->getJson('/api/company/forms_info', [
            'Accept-Language' => 'en',
        ]);

        $this->assertApiSuccess($response);
        $response->assertJsonStructure([
            'data' => ['form_fields'],
        ]);
    }

    public function test_public_sectors_list_is_accessible(): void
    {
        $response = $this->getJson('/api/company/sectors');

        $this->assertApiSuccess($response);
    }

    public function test_company_products_require_authentication(): void
    {
        $response = $this->getJson('/api/company/products');

        $response->assertStatus(401);
    }

    public function test_active_company_user_can_get_products(): void
    {
        $user = $this->createCompanyUser();
        Sanctum::actingAs($user, ['company']);

        $response = $this->getJson('/api/company/products');

        $this->assertApiSuccess($response);
    }

    public function test_active_company_user_can_get_dashboard(): void
    {
        $user = $this->createCompanyUser();
        Sanctum::actingAs($user, ['company']);

        $response = $this->getJson('/api/company/company_home');

        $this->assertApiSuccess($response);
    }

    public function test_active_company_user_can_get_profile(): void
    {
        $user = $this->createCompanyUser();
        Sanctum::actingAs($user, ['company']);

        $response = $this->getJson('/api/company/company_profile');

        $this->assertApiSuccess($response);
    }

    public function test_active_company_user_can_get_promotions(): void
    {
        $user = $this->createCompanyUser();
        Sanctum::actingAs($user, ['company']);

        $response = $this->getJson('/api/company/promotions');

        $this->assertApiSuccess($response);
    }

    public function test_active_company_user_can_get_my_requests(): void
    {
        $user = $this->createCompanyUser();
        Sanctum::actingAs($user, ['company']);

        $response = $this->getJson('/api/company/my-requests');

        $this->assertApiSuccess($response);
    }

    public function test_inactive_company_user_is_forbidden_from_protected_routes(): void
    {
        $user = $this->createCompanyUser(active: false);
        Sanctum::actingAs($user, ['company']);

        $response = $this->getJson('/api/company/products');

        $response->assertStatus(403)
            ->assertJsonFragment([
                'message' => 'Your company account has been deactivated by the administrator.',
            ]);
    }

    public function test_store_product_requires_authentication(): void
    {
        $response = $this->postJson('/api/company/store_product', []);

        $response->assertStatus(401);
    }

    public function test_store_product_validation_fails_without_required_fields(): void
    {
        $user = $this->createCompanyUser();
        Sanctum::actingAs($user, ['company']);

        $response = $this->postJson('/api/company/store_product', []);

        $this->assertApiValidationError($response);
    }

    public function test_company_timeline_requires_authentication(): void
    {
        $response = $this->getJson('/api/company/timeline');

        $response->assertStatus(401);
    }

    public function test_authenticated_company_can_get_timeline(): void
    {
        $user = $this->createCompanyUser();
        Sanctum::actingAs($user, ['company']);

        $response = $this->getJson('/api/company/timeline');

        $this->assertApiSuccess($response);
    }
}
