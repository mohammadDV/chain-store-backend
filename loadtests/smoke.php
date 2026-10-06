#!/usr/bin/env php
<?php

/**
 * Lightweight capacity probe when k6 is not installed.
 * Usage: php smoke.php [baseUrl] [concurrency] [durationSeconds]
 * Example: php smoke.php http://localhost/api 20 30
 */

declare(strict_types=1);

$baseUrl = rtrim($argv[1] ?? getenv('BASE_URL') ?: 'http://localhost/api', '/');
$concurrency = max(1, (int) ($argv[2] ?? 10));
$duration = max(5, (int) ($argv[3] ?? 20));

$endpoints = [
    ['GET', '/brands'],
    ['GET', '/categories/all'],
    ['POST', '/products/featured', ['column' => 'order']],
    ['POST', '/products/search', ['query' => 'nike', 'count' => 25, 'page' => 1]],
    ['GET', '/seo/sitemap'],
];

$stats = [
    'ok' => 0,
    'fail' => 0,
    'throttled' => 0,
    'latencies' => [],
    'by_endpoint' => [],
];

$deadline = microtime(true) + $duration;
$start = microtime(true);

echo "Smoke load: {$concurrency} workers × {$duration}s against {$baseUrl}\n";

$children = [];
for ($w = 0; $w < $concurrency; $w++) {
    $pid = pcntl_fork();
    if ($pid === -1) {
        fwrite(STDERR, "fork failed\n");
        exit(1);
    }
    if ($pid === 0) {
        $localOk = 0;
        $localFail = 0;
        $localThrottled = 0;
        $localLat = [];
        $localBy = [];
        while (microtime(true) < $deadline) {
            [$method, $path, $body] = array_merge($endpoints[array_rand($endpoints)], [null]);
            $url = $baseUrl.$path;
            $t0 = microtime(true);
            $ch = curl_init($url);
            $headers = ['Accept: application/json', 'Content-Type: application/json'];
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_CUSTOMREQUEST => $method,
            ]);
            if ($method === 'POST') {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body ?? new stdClass));
            }
            curl_exec($ch);
            $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $ms = (microtime(true) - $t0) * 1000;
            $localLat[] = $ms;
            $key = "{$method} {$path}";
            $localBy[$key] = ($localBy[$key] ?? 0) + 1;
            if ($code === 429) {
                $localThrottled++;
            } elseif ($code >= 200 && $code < 400) {
                $localOk++;
            } else {
                $localFail++;
            }
        }
        file_put_contents(
            sys_get_temp_dir()."/boof_load_{$w}.json",
            json_encode([
                'ok' => $localOk,
                'fail' => $localFail,
                'throttled' => $localThrottled,
                'latencies' => $localLat,
                'by_endpoint' => $localBy,
            ])
        );
        exit(0);
    }
    $children[] = $pid;
}

foreach ($children as $pid) {
    pcntl_waitpid($pid, $status);
}

for ($w = 0; $w < $concurrency; $w++) {
    $file = sys_get_temp_dir()."/boof_load_{$w}.json";
    if (! is_file($file)) {
        continue;
    }
    $part = json_decode((string) file_get_contents($file), true) ?: [];
    @unlink($file);
    $stats['ok'] += (int) ($part['ok'] ?? 0);
    $stats['fail'] += (int) ($part['fail'] ?? 0);
    $stats['throttled'] += (int) ($part['throttled'] ?? 0);
    foreach ($part['latencies'] ?? [] as $lat) {
        $stats['latencies'][] = (float) $lat;
    }
    foreach ($part['by_endpoint'] ?? [] as $ep => $count) {
        $stats['by_endpoint'][$ep] = ($stats['by_endpoint'][$ep] ?? 0) + (int) $count;
    }
}

$elapsed = max(0.001, microtime(true) - $start);
$total = $stats['ok'] + $stats['fail'] + $stats['throttled'];
sort($stats['latencies']);
$p = static function (array $arr, float $pct): float {
    if ($arr === []) {
        return 0.0;
    }
    $idx = (int) floor(($pct / 100) * (count($arr) - 1));

    return $arr[$idx];
};

$report = [
    'base_url' => $baseUrl,
    'concurrency' => $concurrency,
    'duration_s' => round($elapsed, 2),
    'requests' => $total,
    'rps' => round($total / $elapsed, 2),
    'ok' => $stats['ok'],
    'fail' => $stats['fail'],
    'throttled_429' => $stats['throttled'],
    'error_rate' => $total ? round($stats['fail'] / $total, 4) : 0,
    'throttle_rate' => $total ? round($stats['throttled'] / $total, 4) : 0,
    'p50_ms' => round($p($stats['latencies'], 50), 1),
    'p95_ms' => round($p($stats['latencies'], 95), 1),
    'p99_ms' => round($p($stats['latencies'], 99), 1),
    'by_endpoint' => $stats['by_endpoint'],
];

echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL;

file_put_contents(__DIR__.'/last-smoke-result.json', json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);
