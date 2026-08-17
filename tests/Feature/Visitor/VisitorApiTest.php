<?php

namespace Tests\Feature\Visitor;

use App\Models\Company;
use App\Models\Sector;
use App\Models\TicketType;
use Database\Factories\TicketTypeFactory;
use Tests\TestCase;

class VisitorApiTest extends TestCase
{
    public function test_visitor_welcome_page_is_public(): void
    {
        $response = $this->getJson('/api/visitor/welcome-page');

        $this->assertApiSuccess($response);
    }

    public function test_visitor_home_page_is_public(): void
    {
        $response = $this->getJson('/api/visitor/home-page');

        $this->assertApiSuccess($response);
    }

    public function test_visitor_profile_page_is_public(): void
    {
        $response = $this->getJson('/api/visitor/profile-page');

        $this->assertApiSuccess($response);
    }

    public function test_visitor_can_list_companies(): void
    {
        $response = $this->getJson('/api/visitor/companies');

        $this->assertApiSuccess($response);
    }

    public function test_visitor_can_filter_companies_by_sector(): void
    {
        $sector = Sector::create([
            'name' => ['en' => 'Tech', 'ar' => 'تقنية'],
        ]);

        $response = $this->getJson('/api/visitor/companies?sector_id='.$sector->id);

        $this->assertApiSuccess($response);
    }

    public function test_visitor_can_search_companies_by_name(): void
    {
        $response = $this->getJson('/api/visitor/companies?search=Test');

        $this->assertApiSuccess($response);
    }

    public function test_visitor_can_get_company_details(): void
    {
        $user = $this->createCompanyUser();
        $company = $user->company;

        $response = $this->getJson('/api/visitor/companies/'.$company->id);

        $this->assertApiSuccess($response);
    }

    public function test_visitor_company_details_returns_404_for_missing_company(): void
    {
        $response = $this->getJson('/api/visitor/companies/99999');

        $response->assertStatus(404);
    }

    public function test_visitor_transportation_is_public(): void
    {
        $response = $this->getJson('/api/visitor/transportation');

        $this->assertApiSuccess($response);
    }

    public function test_visitor_events_list_is_public(): void
    {
        $response = $this->getJson('/api/visitor/events');

        $this->assertApiSuccess($response);
    }

    public function test_visitor_ticket_types_are_public(): void
    {
        TicketTypeFactory::new()->create(['is_active' => true]);

        $response = $this->getJson('/api/visitor/tickets/types');

        $this->assertApiSuccess($response);
    }

    public function test_visitor_my_tickets_requires_guest_id_header(): void
    {
        $response = $this->getJson('/api/visitor/tickets/my-tickets');

        $this->assertApiSuccess($response);
    }

    public function test_visitor_my_tickets_with_guest_id_header(): void
    {
        $response = $this->getJson('/api/visitor/tickets/my-tickets', [
            'X-Guest-ID' => 'guest-test-uuid-1234',
        ]);

        $this->assertApiSuccess($response);
    }

    public function test_visitor_support_email_requires_fields(): void
    {
        $response = $this->postJson('/api/visitor/support-email', []);

        $this->assertApiValidationError($response);
    }

    public function test_visitor_book_ticket_validation_fails_without_data(): void
    {
        $response = $this->postJson('/api/visitor/tickets/book', []);

        $this->assertApiValidationError($response);
    }
}
