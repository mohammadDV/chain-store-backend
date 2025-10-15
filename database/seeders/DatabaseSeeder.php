<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            ColorSeeder::class,
            BrandSeeder::class,
            CategorySeeder::class,
            ProductSeeder::class,
            BannerSeeder::class,
        ]);
    }
}
