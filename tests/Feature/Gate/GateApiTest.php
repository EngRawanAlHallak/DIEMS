<?php

namespace Tests\Feature\Gate;

use Database\Factories\GateCodeFactory;
use Tests\TestCase;

class GateApiTest extends TestCase
{
    public function test_gate_auth_requires_code(): void
    {
        $response = $this->postJson('/api/gate/auth', []);

        $this->assertApiValidationError($response);
    }

    public function test_gate_auth_succeeds_with_valid_code(): void
    {
        $gateCode = GateCodeFactory::new()->create([
            'code' => 'GATE1234',
            'valid_for_date' => now()->toDateString(),
            'starts_at' => '00:00:00',
            'ends_at' => '23:59:59',
        ]);

        $response = $this->postJson('/api/gate/auth', [
            'code' => $gateCode->code,
        ]);

        $this->assertApiSuccess($response);
        $response->assertJson(['data' => 'AUTHENTICATED']);
    }

    public function test_gate_auth_fails_with_invalid_code(): void
    {
        $response = $this->postJson('/api/gate/auth', [
            'code' => 'INVALID',
        ]);

        $this->assertApiValidationError($response);
    }

    public function test_gate_scan_requires_qr_code(): void
    {
        $response = $this->postJson('/api/gate/scan', []);

        $this->assertApiValidationError($response);
    }

    public function test_gate_scan_rejects_invalid_uuid_format(): void
    {
        $response = $this->postJson('/api/gate/scan', [
            'qr_code' => 'not-a-valid-uuid',
        ]);

        $this->assertApiValidationError($response);
    }

    public function test_gate_scan_returns_error_for_unknown_ticket(): void
    {
        $response = $this->postJson('/api/gate/scan', [
            'qr_code' => '550e8400-e29b-41d4-a716-446655440000',
        ]);

        $response->assertStatus(404);
    }
}
