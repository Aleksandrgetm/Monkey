<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = ['name', 'email', 'password', 'role', 'status', 'email_notifications', 'in_app_notifications', 'reminder_days', 'appearance'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return ['email_verified_at' => 'datetime', 'password' => 'hashed', 'role' => 'integer', 'status' => 'integer', 'email_notifications' => 'boolean', 'in_app_notifications' => 'boolean', 'reminder_days' => 'integer'];
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === 1 && $this->status === 1;
    }

    public function reminderDays(): int
    {
        return $this->reminder_days ?? (int) SystemSetting::getValue('reminder_days', 30);
    }
}
