<?php

namespace Tests;

use Domain\User\Services\TelegramNotificationService;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Cache;
use RuntimeException;
use Tests\Support\Fakes\FakeTelegramNotificationService;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        // BEFORE app boot / RefreshDatabase — abort if env still points at a real DB.
        $this->guardTestDatabaseEnv();

        parent::setUp();

        // AFTER boot — abort if config somehow still points at MySQL/real data.
        $this->assertUsingIsolatedTestDatabase();

        // Never let Redis (or leftover unique locks) leak across tests.
        config(['cache.default' => 'array']);
        Cache::setDefaultDriver('array');
        Cache::flush();

        $this->app->instance(
            TelegramNotificationService::class,
            new FakeTelegramNotificationService
        );
    }

    /**
     * Hard stop before RefreshDatabase can migrate:fresh a real database
     * (e.g. Docker MySQL boofstore_db when $_SERVER leaked past phpunit.xml).
     */
    private function guardTestDatabaseEnv(): void
    {
        $connection = (string) ($_SERVER['DB_CONNECTION'] ?? $_ENV['DB_CONNECTION'] ?? getenv('DB_CONNECTION') ?: '');
        $database = (string) ($_SERVER['DB_DATABASE'] ?? $_ENV['DB_DATABASE'] ?? getenv('DB_DATABASE') ?: '');

        if ($connection !== 'sqlite' || $database !== ':memory:') {
            throw new RuntimeException(
                "Refusing to run tests: DB must be sqlite :memory: before boot, got [{$connection}]/[{$database}]. ".
                'Check tests/bootstrap.php — never run the suite against MySQL/production.'
            );
        }
    }

    private function assertUsingIsolatedTestDatabase(): void
    {
        $default = (string) config('database.default');
        $database = (string) config("database.connections.{$default}.database");

        if ($default !== 'sqlite' || $database !== ':memory:') {
            throw new RuntimeException(
                "Refusing to continue tests: isolated DB required, got [{$default}]/[{$database}]. ".
                'RefreshDatabase would destroy real data.'
            );
        }
    }
}
