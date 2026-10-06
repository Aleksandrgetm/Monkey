<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        JsonResource::withoutWrapping();
        $resetUrl = fn (User $user, string $token): string => rtrim(config('app.frontend_url'), '/').'/reset-password?'.http_build_query(['token' => $token, 'email' => $user->email]);
        ResetPassword::createUrlUsing($resetUrl);
        ResetPassword::toMailUsing(fn (User $user, string $token): MailMessage => (new MailMessage)
            ->subject('Scan & Save: paroles atjaunošana')
            ->view('emails.reset-password', ['name' => $user->name, 'resetUrl' => $resetUrl($user, $token), 'expiresInMinutes' => config('auth.passwords.'.config('auth.defaults.passwords').'.expire')]));
        RateLimiter::for('login', fn (Request $request): array => [Limit::perMinute(10)->by('login-ip:'.$request->ip()), Limit::perMinute(5)->by('login:'.strtolower((string) $request->input('email')).'|'.$request->ip())]);
        RateLimiter::for('registration', fn (Request $request): Limit => Limit::perMinute(5)->by($request->ip()));
        RateLimiter::for('password-reset', fn (Request $request): Limit => Limit::perMinute(5)->by($request->ip()));
    }
}
