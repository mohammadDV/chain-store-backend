<?php

namespace Database\Seeders;

use Domain\User\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin role
        Role::create([
            'id' => 1,
            'name' => 'admin',
            'guard_name' => 'web',
            'created_at' => '2025-05-30 10:55:45',
            'updated_at' => '2025-05-30 10:55:45',
        ]);

        // Create user role
        Role::create([
            'id' => 2,
            'name' => 'user',
            'guard_name' => 'web',
            'created_at' => '2025-05-30 10:55:45',
            'updated_at' => '2025-05-30 10:55:45',
        ]);
    }
}
