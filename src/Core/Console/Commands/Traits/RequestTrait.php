<?php

namespace Core\Console\Commands\Traits;

use Domain\Product\Services\OxylabsService;

trait RequestTrait
{
    private function retryRequest(OxylabsService $oxylabsService, string $type, string $url, int $attempt = 1): array
    {
        $this->info("URL: " . $url);
        $this->info("Attempt: " . $attempt);
        $this->info("--------------------------------");

        $filters = $oxylabsService->fetchRequest($type, $url);

        if(empty($filters) || !empty($filters['status']) && $filters['status'] == 2) {
            if($attempt >= 3) {
                $this->error("Connection error after 3 attempts: ");
                return $filters;
            }

            $this->error("Connection error (attempt {$attempt}/3): ");
            sleep(5);
            return $this->retryRequest($oxylabsService, $type, $url, $attempt + 1);
        }

        return $filters;

    }
}
