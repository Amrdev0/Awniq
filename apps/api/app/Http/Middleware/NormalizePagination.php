<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NormalizePagination
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->query->has('per_page')) {
            $perPage = filter_var($request->query('per_page'), FILTER_VALIDATE_INT);
            $request->query->set('per_page', min(max($perPage ?: 15, 1), 100));
        }

        if ($request->query->has('page')) {
            $page = filter_var($request->query('page'), FILTER_VALIDATE_INT);
            $request->query->set('page', max($page ?: 1, 1));
        }

        return $next($request);
    }
}
