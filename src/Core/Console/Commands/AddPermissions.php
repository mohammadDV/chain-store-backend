<?php

namespace Core\Console\Commands;

use Database\Seeders\AdminPermissionSeeder;
use Domain\User\Models\Role;
use Illuminate\Console\Command;

class AddPermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:add-permissions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed Spatie roles and Filament admin permissions';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        Role::updateOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);

        $this->callSilent('db:seed', ['--class' => AdminPermissionSeeder::class]);

        $this->info('Roles and admin permissions synced.');

        return self::SUCCESS;
    }
}
