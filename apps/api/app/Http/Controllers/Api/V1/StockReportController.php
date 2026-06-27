<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\StockLotResource;
use App\Services\StockReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StockReportController extends Controller
{
    public function summary(Request $request, StockReportService $stockReportService): JsonResponse
    {
        $warehouseId = $request->integer('warehouse_id') ?: null;

        return response()->json([
            'data' => $stockReportService->summary($request->user()->organization_id, $warehouseId),
        ]);
    }

    public function lowStock(Request $request, StockReportService $stockReportService): JsonResponse
    {
        return response()->json([
            'data' => $stockReportService->lowStock($request->user()->organization_id),
        ]);
    }

    public function expiring(Request $request, StockReportService $stockReportService): JsonResponse
    {
        $validated = $request->validate([
            'days' => ['nullable', 'integer', 'min:1', 'max:365'],
        ]);

        return response()->json([
            'data' => StockLotResource::collection($stockReportService->expiring(
                $request->user()->organization_id,
                (int) ($validated['days'] ?? 30),
            ))->resolve($request),
        ]);
    }
}
