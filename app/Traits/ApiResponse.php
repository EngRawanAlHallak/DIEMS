<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

trait ApiResponse
{
    protected function success(mixed $data = null, string $message = 'Success', int $status = 200): JsonResponse
    {
        return response()->json([
            'status'  => 'success',
            'message' => $message,
            'data'    => $data,
        ], $status);
    }

    protected function created(mixed $data = null, string $message = 'Created successfully'): JsonResponse
    {
        return $this->success($data, $message, 201);
    }

    protected function error(string $message, int $status = 400, mixed $errors = null): JsonResponse
    {
        return response()->json([
            'status'  => 'DENIED',
            'message' => $message,
            'errors'  => $errors,
        ], $status);
    }

    protected function failed(string $message = 'Failed'): JsonResponse
    {
        return $this->error($message, 400);
    }
    protected function unauthorized(string $message = 'Unauthenticated'): JsonResponse
    {
        return $this->error($message, 401);
    }

    protected function forbidden(string $message = 'Forbidden'): JsonResponse
    {
        return $this->error($message, 403);
    }

    protected function notFound(string $message = 'Resource not found'): JsonResponse
    {
        return $this->error($message, 404);
    }

    protected function tooManyRequests(string $message = 'Too many requests'): JsonResponse
    {
        return $this->error($message, 429);
    }

    protected function invalidToken(string $message = 'Invalid QR code'): JsonResponse
    {
        return $this->error($message, 404);
    }

    protected function usedQR(mixed $errors = null,string $message = 'Used QR code'): JsonResponse
    {
        return $this->error($message, 404, 'usedAt:'.$errors);
    }
}
