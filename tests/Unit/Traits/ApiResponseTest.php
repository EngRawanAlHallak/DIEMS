<?php

namespace Tests\Unit\Traits;

use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Tests\TestCase;

class ApiResponseTest extends TestCase
{
    private object $responder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->responder = new class
        {
            use ApiResponse;

            public function callSuccess(mixed $data = null, string $message = 'Success', int $status = 200): JsonResponse
            {
                return $this->success($data, $message, $status);
            }

            public function callCreated(mixed $data = null): JsonResponse
            {
                return $this->created($data);
            }

            public function callError(string $message, int $status = 400): JsonResponse
            {
                return $this->error($message, $status);
            }

            public function callUnauthorized(): JsonResponse
            {
                return $this->unauthorized();
            }

            public function callForbidden(): JsonResponse
            {
                return $this->forbidden();
            }

            public function callNotFound(): JsonResponse
            {
                return $this->notFound();
            }
        };
    }

    public function test_success_response_has_correct_structure(): void
    {
        $response = $this->responder->callSuccess(['id' => 1], 'OK');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'OK',
                'data' => ['id' => 1],
            ]);
    }

    public function test_created_response_returns_201(): void
    {
        $response = $this->responder->callCreated(['id' => 1]);

        $response->assertStatus(201)
            ->assertJson(['status' => 'success']);
    }

    public function test_error_response_has_denied_status(): void
    {
        $response = $this->responder->callError('Bad request', 400);

        $response->assertStatus(400)
            ->assertJson([
                'status' => 'DENIED',
                'message' => 'Bad request',
            ]);
    }

    public function test_unauthorized_returns_401(): void
    {
        $this->responder->callUnauthorized()->assertStatus(401);
    }

    public function test_forbidden_returns_403(): void
    {
        $this->responder->callForbidden()->assertStatus(403);
    }

    public function test_not_found_returns_404(): void
    {
        $this->responder->callNotFound()->assertStatus(404);
    }
}
