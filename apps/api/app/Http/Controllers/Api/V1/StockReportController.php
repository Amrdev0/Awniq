<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\StockLotResource;
use App\Services\StockReportService;
use App\Support\PaginationResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StockReportController extends Controller
{
    public function summary(Request $request, StockReportService $stockReportService): JsonResponse
    {
        $warehouseId = $request->integer('warehouse_id') ?: null;

        $rows = $stockReportService->summary($request->user()->organization_id, $warehouseId);

        return PaginationResponse::make($this->filterRows($rows, $request->query('search')), $request);
    }

    public function lowStock(Request $request, StockReportService $stockReportService): JsonResponse
    {
        $rows = $stockReportService->lowStock($request->user()->organization_id);

        return PaginationResponse::make($this->filterRows($rows, $request->query('search')), $request);
    }

    public function expiring(Request $request, StockReportService $stockReportService): JsonResponse
    {
        $validated = $request->validate([
            'days' => ['nullable', 'integer', 'min:1', 'max:365'],
        ]);

        $rows = StockLotResource::collection($stockReportService->expiring(
            $request->user()->organization_id,
            (int) ($validated['days'] ?? 30),
        ))->resolve($request);

        if ($search = $request->query('search')) {
            $rows = array_values(array_filter($rows, fn (array $row): bool => str_contains(strtolower(json_encode($row)), strtolower($search))));
        }

        return PaginationResponse::make($rows, $request);
    }

    /** @param list<array<string, mixed>> $rows */
    private function filterRows(array $rows, ?string $search): array
    {
        if (! $search) {
            return $rows;
        }

        $needle = strtolower($search);

        return array_values(array_filter($rows, fn (array $row): bool => str_contains(strtolower(implode(' ', array_map('strval', $row))), $needle)));
    }
}
