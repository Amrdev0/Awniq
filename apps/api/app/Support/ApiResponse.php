<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;

class ApiResponse
{
    /**
     * @param  array<string, mixed>|object  $data
     */
    public static function success(array|object $data = [], string $message = 'Operation completed successfully.', int $status = 200): JsonResponse
    {
        return response()->json([
            'data' => $data,
            'message' => $message,
        ], $status);
    }
}
