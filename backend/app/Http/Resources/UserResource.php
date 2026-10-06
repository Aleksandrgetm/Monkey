<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'name' => $this->name, 'email' => $this->email, 'role' => $this->role, 'status' => $this->status, 'created_at' => $this->created_at?->toISOString(), 'email_notifications' => $this->email_notifications, 'in_app_notifications' => $this->in_app_notifications, 'reminder_days' => $this->reminderDays(), 'reminder_days_override' => $this->reminder_days, 'appearance' => $this->appearance];
    }
}
