<?php

namespace Database\Seeders;

use Domain\AdminAccess\Services\AdminAccessService;
use Domain\User\Models\User;
use Illuminate\Database\Seeder;

class AdminPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $access = app(AdminAccessService::class);
        $access->ensurePermissionsExist();

        User::query()
            ->whereIn('email', config('admin.super_admin_emails', []))
            ->update(['level' => 3]);
    }
}
