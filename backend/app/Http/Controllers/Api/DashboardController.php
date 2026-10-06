<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\DocumentResource;
use App\Services\WarrantyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request, WarrantyService $warranty): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'documents' => $user->documents()->count(),
            'receipts' => $user->documents()->where('kind', 'receipt')->count(),
            'warranties' => $user->documents()->where('kind', 'warranty')->count(),
            'products' => $user->products()->count(),
            'expiring' => $warranty->filter($user->documents()->getQuery(), 'expiring', $user->reminderDays())->count(),
            'unread' => $user->alerts()->where('in_app', true)->where('status', 0)->count(),
            'recent' => DocumentResource::collection($user->documents()->metadata()->with(['category', 'product'])->latest()->limit(6)->get()),
        ]);
    }
}
