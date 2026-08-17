<?php

namespace Tests\Feature;

use Tests\TestCase;

class WebRoutesTest extends TestCase
{
    public function test_welcome_page_returns_200(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_payment_success_page_returns_200(): void
    {
        $response = $this->get('/payment/success');

        $response->assertStatus(200);
    }

    public function test_health_check_endpoint_returns_200(): void
    {
        $response = $this->get('/up');

        $response->assertStatus(200);
    }

    public function test_signed_company_payment_link_requires_valid_signature(): void
    {
        $response = $this->get('/company/payments/pay/1');

        $response->assertStatus(403);
    }

    public function test_signed_event_payment_link_requires_valid_signature(): void
    {
        $response = $this->get('/event/payments/pay/1');

        $response->assertStatus(403);
    }
}
