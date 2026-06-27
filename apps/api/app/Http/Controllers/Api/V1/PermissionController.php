<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\PermissionResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return PermissionResource::collection(Permission::query()->orderBy('name')->get());
    }
}
