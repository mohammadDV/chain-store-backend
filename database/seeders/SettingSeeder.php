<?php

namespace Database\Seeders;

use Domain\Setting\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Setting::firstOrCreate(
            ['id' => 1],
            [
                'profit_rate' => config('setting.profit_rate', 40),
                'exchange_rate' => config('setting.exchange_rate', 3000),
            ]
        );
    }
}