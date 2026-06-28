<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class VersionController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return ApiResponse::success([
            'service' => 'awniq-api',
            'version' => config('app.version'),
            'commit' => config('app.commit'),
            'environment' => app()->environment(),
        ]);
    }
}
