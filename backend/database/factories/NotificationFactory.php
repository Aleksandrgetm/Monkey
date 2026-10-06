<?php

namespace Database\Factories;

use App\Models\Document;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class NotificationFactory extends Factory
{
    public function definition(): array
    {
        return ['user_id' => User::factory(), 'document_id' => fn (array $attributes): int => Document::factory()->create(['user_id' => $attributes['user_id']])->id, 'message' => 'Your warranty expires soon.', 'kind' => 'warranty', 'status' => 0, 'notification_date' => now(), 'in_app' => true];
    }
}
