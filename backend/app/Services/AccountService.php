<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AccountService
{
    public function protectLastAdministrator(User $user, bool $removingAccess): void
    {
        $admins = User::where('role', 1)->where('status', 1)->orderBy('id')->lockForUpdate()->get(['id']);
        if ($removingAccess && $user->isAdmin() && $admins->count() <= 1) {
            throw ValidationException::withMessages(['role' => 'The last active administrator cannot be removed, blocked, or demoted.']);
        }
    }

    public function revokeSessions(User $user, ?string $except = null): void
    {
        if (config('session.driver') === 'database') {
            $query = DB::connection(config('session.connection'))->table(config('session.table', 'sessions'))->where('user_id', $user->id);
            if ($except !== null) {
                $query->where('id', '!=', $except);
            }
            $query->delete();
        }
    }

    public function delete(User $user): void
    {
        DB::transaction(function () use ($user): void {
            $this->protectLastAdministrator($user, true);
            $this->revokeSessions($user);
            DB::table('password_reset_tokens')->where('email', $user->email)->delete();
            $user->alerts()->delete();
            $user->documents()->delete();
            $user->products()->delete();
            $user->categories()->delete();
            $user->delete();
        });
    }
}
