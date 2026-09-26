<?php

/**
 * Force test env into putenv/$_ENV/$_SERVER before Laravel boots.
 *
 * PHPUnit's force="true" updates putenv + $_ENV but not $_SERVER. Docker/.env
 * values in $_SERVER (CACHE_STORE=redis, DB_CONNECTION=mysql, …) then win
 * inside Laravel's env() helper and leak Redis/MySQL into the suite — which
 * previously let RefreshDatabase wipe the real boofstore_db.
 */
$forced = [
    'APP_ENV' => 'testing',
    'APP_MAINTENANCE_DRIVER' => 'file',
    'BCRYPT_ROUNDS' => '4',
    'CACHE_STORE' => 'array',
    'DB_CONNECTION' => 'sqlite',
    'DB_DATABASE' => ':memory:',
    'MAIL_MAILER' => 'array',
    'PULSE_ENABLED' => 'false',
    'QUEUE_CONNECTION' => 'sync',
    'SESSION_DRIVER' => 'array',
    'TELESCOPE_ENABLED' => 'false',
];

foreach ($forced as $key => $value) {
    putenv("{$key}={$value}");
    $_ENV[$key] = $value;
    $_SERVER[$key] = $value;
}

$connection = $_SERVER['DB_CONNECTION'] ?? '';
$database = $_SERVER['DB_DATABASE'] ?? '';

if ($connection !== 'sqlite' || $database !== ':memory:') {
    fwrite(STDERR, "[tests/bootstrap] Refusing to boot tests: DB must be sqlite :memory:, got {$connection}/{$database}\n");
    exit(1);
}

require __DIR__.'/../vendor/autoload.php';
