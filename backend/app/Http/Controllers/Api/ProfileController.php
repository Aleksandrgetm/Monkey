<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProfileRequest;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\DocumentResource;
use App\Http\Resources\NotificationResource;
use App\Http\Resources\ProductResource;
use App\Http\Resources\UserResource;
use App\Services\AccountService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        return response()->json(['user' => new UserResource($request->user())]);
    }

    public function update(ProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        if ($request->has('email') && $request->input('email') !== $user->email) {
            DB::table('password_reset_tokens')->where('email', $user->email)->delete();
            $user->email_verified_at = null;
        }
        $user->fill($request->safe()->only(['name', 'email']))->save();

        return response()->json(['user' => new UserResource($user)]);
    }

    public function password(ProfileRequest $request, AccountService $accounts): JsonResponse
    {
        $user = $request->user();
        $user->forceFill(['password' => $request->validated('password'), 'remember_token' => Str::random(60)])->save();
        $accounts->revokeSessions($user, $request->session()->getId());
        DB::table('password_reset_tokens')->where('email', $user->email)->delete();
        $request->session()->regenerate();

        return response()->json(['message' => 'Parole ir nomainīta.']);
    }

    public function preferences(ProfileRequest $request): JsonResponse
    {
        $request->user()->update($request->validated());

        return response()->json(['user' => new UserResource($request->user())]);
    }

    public function destroy(ProfileRequest $request, AccountService $accounts): Response
    {
        $accounts->delete($request->user());
        Auth::logoutCurrentDevice();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->noContent();
    }

    public function export(Request $request): StreamedResponse
    {
        $user = $request->user();

        return response()->streamDownload(function () use ($user, $request): void {
            $data = [
                'exported_at' => now()->toISOString(),
                'user' => (new UserResource($user))->resolve($request),
                'categories' => CategoryResource::collection($user->categories()->withCount(['documents', 'products'])->get())->resolve($request),
                'products' => ProductResource::collection($user->products()->with('category')->withCount('documents')->get())->resolve($request),
                'documents' => DocumentResource::collection($user->documents()->metadata()->with(['category', 'product'])->get())->resolve($request),
                'notifications' => NotificationResource::collection($user->alerts()->with('document')->get())->resolve($request),
            ];
            echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        }, 'scan-save-export-'.now()->format('Y-m-d').'.json', ['Content-Type' => 'application/json', 'Cache-Control' => 'private, no-store']);
    }
}
