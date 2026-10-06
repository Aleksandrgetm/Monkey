<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminRequest;
use App\Http\Resources\UserResource;
use App\Models\Category;
use App\Models\Document;
use App\Models\Notification;
use App\Models\Product;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\AccountService;
use App\Services\WarrantyService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminController extends Controller
{
    public function users(AdminRequest $request): JsonResponse
    {
        $data = $request->validated();
        $query = User::query();
        if (! empty($data['search'])) {
            $search = '%'.$data['search'].'%';
            $query->where(function (Builder $query) use ($search): void {
                $query->where('name', 'like', $search)->orWhere('email', 'like', $search);
            });
        }
        foreach (['role', 'status'] as $field) {
            if (isset($data[$field])) {
                $query->where($field, $data[$field]);
            }
        }

        return $this->paginated($query->orderBy($data['sort'] ?? 'created_at', $data['direction'] ?? 'desc')->orderBy('id')->paginate($request->integer('per_page', 20)), UserResource::class);
    }

    public function updateUser(AdminRequest $request, User $user, AccountService $accounts): UserResource
    {
        DB::transaction(function () use ($request, $user, $accounts): void {
            $data = $request->validated();
            $accounts->protectLastAdministrator($user, (isset($data['role']) && (int) $data['role'] !== 1) || (isset($data['status']) && (int) $data['status'] !== 1));
            if (isset($data['email']) && $data['email'] !== $user->email) {
                DB::table('password_reset_tokens')->where('email', $user->email)->delete();
                $user->email_verified_at = null;
            }
            $user->fill($data);
            if ($user->isDirty(['role', 'status', 'email'])) {
                $accounts->revokeSessions($user, $user->id === $request->user()->id ? $request->session()->getId() : null);
                $user->remember_token = Str::random(60);
            }
            $user->save();
        });

        return new UserResource($user);
    }

    public function deleteUser(AdminRequest $request, User $user, AccountService $accounts): Response
    {
        $accounts->delete($user);
        if ($user->id === $request->user()->id) {
            Auth::logoutCurrentDevice();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return response()->noContent();
    }

    public function stats(WarrantyService $warranty): JsonResponse
    {
        return response()->json($this->statistics($warranty));
    }

    public function settings(): JsonResponse
    {
        return response()->json(['registration_enabled' => (bool) SystemSetting::getValue('registration_enabled', true), 'reminder_days' => (int) SystemSetting::getValue('reminder_days', 30)]);
    }

    public function updateSettings(AdminRequest $request): JsonResponse
    {
        foreach ($request->validated() as $key => $value) {
            SystemSetting::setValue($key, $value);
        }

        return $this->settings();
    }

    public function health(): JsonResponse
    {
        DB::select('SELECT 1');

        return response()->json(['database' => 'ok', 'scheduler_last_run' => SystemSetting::getValue('scheduler_last_run'), 'mail_mailer' => config('mail.default'), 'last_backup_at' => SystemSetting::getValue('last_backup_at')]);
    }

    public function report(WarrantyService $warranty): StreamedResponse
    {
        $statistics = $this->statistics($warranty);

        return response()->streamDownload(function () use ($statistics): void {
            $stream = fopen('php://output', 'wb');
            fputcsv($stream, ['Metric', 'Value'], escape: '');
            foreach ($statistics as $metric => $value) {
                fputcsv($stream, [$metric, $value], escape: '');
            }
            fclose($stream);
        }, 'scan-save-report-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8', 'Cache-Control' => 'private, no-store']);
    }

    private function statistics(WarrantyService $warranty): array
    {
        $days = (int) SystemSetting::getValue('reminder_days', 30);

        return ['users' => User::count(), 'blocked_users' => User::where('status', 0)->count(), 'documents' => Document::count(), 'products' => Product::count(), 'categories' => Category::count(), 'notifications' => Notification::count(), 'storage_bytes' => (int) Document::sum('file_size'), 'expiring' => $warranty->filter(Document::query(), 'expiring', $days)->count(), 'expired' => $warranty->filter(Document::query(), 'expired', $days)->count()];
    }
}
