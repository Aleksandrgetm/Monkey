<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $data = $request->validate(['status' => ['sometimes', 'nullable', Rule::in([0, 1])], 'kind' => ['sometimes', 'nullable', Rule::in(['warranty', 'system'])], 'page' => ['sometimes', 'integer', 'min:1'], 'per_page' => ['sometimes', 'integer', 'min:1', 'max:100']]);
        $query = $request->user()->alerts()->where('in_app', true)->with('document');
        foreach (['status', 'kind'] as $field) {
            if (isset($data[$field])) {
                $query->where($field, $data[$field]);
            }
        }

        return $this->paginated($query->latest()->orderByDesc('id')->paginate($request->integer('per_page', 20)), NotificationResource::class);
    }

    public function update(Request $request, Notification $notification): NotificationResource
    {
        abort_unless($notification->user_id === $request->user()->id && $notification->in_app, 404);
        Gate::authorize('update', $notification);
        $notification->update($request->validate(['status' => ['required', Rule::in([1])]]));

        return new NotificationResource($notification->load('document'));
    }

    public function readAll(Request $request): JsonResponse
    {
        $count = $request->user()->alerts()->where('in_app', true)->where('status', 0)->update(['status' => 1]);

        return response()->json(['message' => 'Paziņojumi atzīmēti kā izlasīti.', 'updated' => $count]);
    }
}
