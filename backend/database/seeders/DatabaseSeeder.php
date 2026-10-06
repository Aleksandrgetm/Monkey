<?php

namespace Database\Seeders;

use App\Models\SystemSetting;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        SystemSetting::firstOrCreate(['key' => 'registration_enabled'], ['value' => true]);
        SystemSetting::firstOrCreate(['key' => 'reminder_days'], ['value' => 30]);
    }
}
