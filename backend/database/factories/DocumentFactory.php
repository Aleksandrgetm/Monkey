<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class DocumentFactory extends Factory
{
    public function definition(): array
    {
        $content = "%PDF-1.4\n1 0 obj<</Type/Catalog>>endobj\n%%EOF\n";

        return ['user_id' => User::factory(), 'category_id' => fn (array $attributes): int => Category::factory()->create(['user_id' => $attributes['user_id']])->id, 'product_id' => null, 'name' => fake()->words(3, true), 'file_name' => 'receipt.pdf', 'file_type' => 'application/pdf', 'file_size' => strlen($content), 'file_content' => $content, 'kind' => 'receipt', 'merchant' => fake()->company(), 'amount' => fake()->randomFloat(2, 0, 99999), 'purchase_date' => now()->subMonth()->toDateString(), 'warranty_end_date' => now()->addYear()->toDateString(), 'note' => null];
    }
}
