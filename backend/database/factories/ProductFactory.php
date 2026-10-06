<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    public function definition(): array
    {
        return ['user_id' => User::factory(), 'category_id' => fn (array $attributes): int => Category::factory()->create(['user_id' => $attributes['user_id']])->id, 'name' => fake()->words(3, true), 'merchant' => fake()->company(), 'amount' => fake()->randomFloat(2, 0, 99999), 'purchase_date' => now()->subMonth()->toDateString(), 'warranty_end_date' => now()->addYear()->toDateString(), 'note' => null];
    }
}
