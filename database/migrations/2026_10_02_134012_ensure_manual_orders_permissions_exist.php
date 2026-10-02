<?php

use Domain\AdminAccess\Services\AdminAccessService;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Sync Spatie permissions from AdminPermission definitions.
     * Fixes production when new modules (e.g. manual_orders) ship without re-seeding.
     */
    public function up(): void
    {
        app(AdminAccessService::class)->ensurePermissionsExist();
    }

    public function down(): void
    {
        // Permissions are additive; do not delete on rollback.
    }
};
