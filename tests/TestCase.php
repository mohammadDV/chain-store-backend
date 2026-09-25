<?php

namespace Tests;

use Domain\User\Services\TelegramNotificationService;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Tests\Support\Fakes\FakeTelegramNotificationService;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->app->instance(
            TelegramNotificationService::class,
            new FakeTelegramNotificationService
        );
    }
}
