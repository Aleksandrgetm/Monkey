<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AuthRequest;
use App\Http\Resources\UserResource;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\AccountService;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function csrf(Request $request): JsonResponse
    {
        return response()->json(['token' => $request->session()->token()]);
    }

    public function register(AuthRequest $request): JsonResponse
    {
        abort_unless(SystemSetting::getValue('registration_enabled', true), 403, 'Reģistrācija pašlaik ir atspējota.');
        $user = DB::transaction(function () use ($request): User {
            $user = User::create($request->safe()->only(['name', 'email', 'password']));
            foreach (['Elektronika', 'Mājas preces', 'Citi'] as $name) {
                $user->categories()->create(['name' => $name]);
            }

            return $user->fresh();
        });
        Auth::login($user);
        $request->session()->regenerate();

        return response()->json(['user' => new UserResource($user)], 201);
    }

    public function login(AuthRequest $request): JsonResponse
    {
        $user = User::where('email', $request->validated('email'))->first();
        if ($user && Hash::check($request->validated('password'), $user->password) && $user->status !== 1) {
            abort(403, 'Šis konts ir bloķēts. Sazinieties ar administratoru.');
        }
        if (! Auth::attempt(['email' => $request->validated('email'), 'password' => $request->validated('password'), 'status' => 1], $request->boolean('remember'))) {
            throw ValidationException::withMessages(['email' => 'The provided email or password is incorrect.']);
        }
        $request->session()->regenerate();

        return response()->json(['user' => new UserResource($request->user())]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(['user' => new UserResource($request->user())]);
    }

    public function logout(Request $request): Response
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->noContent();
    }

    public function forgotPassword(AuthRequest $request): JsonResponse
    {
        Password::sendResetLink($request->safe()->only(['email']));

        return response()->json(['message' => 'Ja šai e-pasta adresei ir reģistrēts konts, nosūtījām paroles atjaunošanas saiti.']);
    }

    public function resetPassword(AuthRequest $request, AccountService $accounts): JsonResponse
    {
        $status = Password::reset($request->validated(), function (User $user, string $password) use ($accounts, $request): void {
            $user->forceFill(['password' => $password, 'remember_token' => Str::random(60)])->save();
            $accounts->revokeSessions($user);
            if ($request->user()?->id === $user->id) {
                Auth::logoutCurrentDevice();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }
            event(new PasswordReset($user));
        });
        if ($status !== Password::PasswordReset) {
            throw ValidationException::withMessages(['email' => __($status)]);
        }

        return response()->json(['message' => 'Parole ir atjaunota. Tagad varat pierakstīties.']);
    }
}
