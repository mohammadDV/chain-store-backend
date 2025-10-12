<?php

namespace Core\Console\Commands;

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
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {

        // Add the lite and normal roles
        $admin = Role::updateOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        // $author = Role::updateOrCreate(['name' => 'author', 'guard_name' => 'web']);
        // $operator = Role::updateOrCreate(['name' => 'operator', 'guard_name' => 'web']);
        $user = Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
    }
}
