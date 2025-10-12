<?php

namespace Database\Seeders;

use Domain\Product\Models\Color;
use Illuminate\Database\Seeder;

class ColorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $colors = [
            ['title' => 'مشکی', 'code' => '#000000', 'status' => 1, 'priority' => 1],
            ['title' => 'سفید', 'code' => '#FFFFFF', 'status' => 1, 'priority' => 2],
            ['title' => 'قرمز', 'code' => '#FF0000', 'status' => 1, 'priority' => 3],
            ['title' => 'آبی', 'code' => '#0000FF', 'status' => 1, 'priority' => 4],
            ['title' => 'سبز', 'code' => '#00FF00', 'status' => 1, 'priority' => 5],
            ['title' => 'زرد', 'code' => '#FFFF00', 'status' => 1, 'priority' => 6],
            ['title' => 'بنفش', 'code' => '#800080', 'status' => 1, 'priority' => 7],
            ['title' => 'نارنجی', 'code' => '#FFA500', 'status' => 1, 'priority' => 8],
            ['title' => 'ارغوانی', 'code' => '#FFC0CB', 'status' => 1, 'priority' => 9],
            ['title' => 'خاکستری', 'code' => '#808080', 'status' => 1, 'priority' => 10],
        ];

        foreach ($colors as $color) {
            Color::create($color);
        }
    }
}
