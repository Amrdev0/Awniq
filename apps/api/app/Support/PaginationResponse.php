<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaginationResponse
{
    /**
     * @param  list<mixed>  $rows
     */
    public static function make(array $rows, Request $request, int $defaultPerPage = 15): JsonResponse
    {
        $page = max($request->integer('page', 1), 1);
        $perPage = $request->integer('per_page', $defaultPerPage);
        $total = count($rows);
        $lastPage = max((int) ceil($total / $perPage), 1);
        $offset = ($page - 1) * $perPage;
        $data = array_values(array_slice($rows, $offset, $perPage));
        $url = fn (int $target): string => $request->fullUrlWithQuery(['page' => $target, 'per_page' => $perPage]);

        return response()->json([
            'data' => $data,
            'links' => [
                'first' => $url(1),
                'last' => $url($lastPage),
                'prev' => $page > 1 ? $url($page - 1) : null,
                'next' => $page < $lastPage ? $url($page + 1) : null,
            ],
            'meta' => [
                'current_page' => $page,
                'from' => $data === [] ? null : $offset + 1,
                'last_page' => $lastPage,
                'path' => $request->url(),
                'per_page' => $perPage,
                'to' => $data === [] ? null : $offset + count($data),
                'total' => $total,
            ],
        ]);
    }
}
