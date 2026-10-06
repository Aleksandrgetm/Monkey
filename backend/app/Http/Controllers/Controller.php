<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;

abstract class Controller
{
    protected function paginated(LengthAwarePaginator $paginator, string $resource): JsonResponse
    {
        return response()->json(['data' => $resource::collection($paginator->getCollection())->resolve(), 'current_page' => $paginator->currentPage(), 'last_page' => $paginator->lastPage(), 'total' => $paginator->total(), 'per_page' => $paginator->perPage()]);
    }
}
