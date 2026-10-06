<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        User::each(function (User $user): void {
            foreach (['Elektronika', 'Mājas preces', 'Citi'] as $name) {
                $user->categories()->firstOrCreate(['name' => $name]);
            }
        });
    }
}
