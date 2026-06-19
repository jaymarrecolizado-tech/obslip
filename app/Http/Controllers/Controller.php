<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;

abstract class Controller
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    protected function successResponse(
        mixed $data = null,
        ?string $message = 'Success',
        int $status = 200
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'data' => $data,
            'message' => $message,
            'errors' => null,
        ], $status);
    }

    protected function errorResponse(
        string $message = 'Error occurred.',
        int $status = 400,
        array $errors = []
    ): JsonResponse {
        return response()->json([
            'success' => false,
            'data' => null,
            'message' => $message,
            'errors' => $errors,
        ], $status);
    }

    protected function validationErrorResponse(
        array $errors
    ): JsonResponse {
        return response()->json([
            'success' => false,
            'data' => null,
            'message' => 'Validation failed.',
            'errors' => $errors,
        ], 422);
    }

    protected function notFoundResponse(
        string $message = 'Resource not found.'
    ): JsonResponse {
        return $this->errorResponse($message, 404);
    }

    protected function unauthorizedResponse(
        string $message = 'Unauthorized.'
    ): JsonResponse {
        return $this->errorResponse($message, 401);
    }

    protected function forbiddenResponse(
        string $message = 'Forbidden.'
    ): JsonResponse {
        return $this->errorResponse($message, 403);
    }
}