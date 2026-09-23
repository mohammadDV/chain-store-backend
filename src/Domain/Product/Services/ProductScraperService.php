<?php

namespace Domain\Product\Services;

use Domain\Product\Exceptions\ProductScraperException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProductScraperService
{
    public function preview(array $payload): array
    {
        return $this->request('/preview', $payload);
    }

    public function apply(array $payload): array
    {
        return $this->request('/apply', $payload);
    }

    public function payload(?string $url, ?string $code, int $brandId, ?int $categoryId): array
    {
        $body = [
            'brand_id' => $brandId,
        ];

        if (! empty($url)) {
            $body['url'] = $url;
        }

        if (! empty($code)) {
            $body['code'] = $code;
        }

        if ($categoryId) {
            $body['category_id'] = $categoryId;
        }

        return $body;
    }

    private function request(string $path, array $payload): array
    {
        $baseUrl = rtrim((string) config('product_scraper.url'), '/');
        if ($baseUrl === '') {
            throw new ProductScraperException(__('site.scraper_not_configured'));
        }

        $pending = Http::timeout((int) config('product_scraper.timeout', 180))
            ->connectTimeout(10)
            ->acceptJson()
            ->asJson();

        $token = config('product_scraper.token');
        if (! empty($token)) {
            $pending = $pending->withToken($token);
        }

        try {
            $response = $pending->post($baseUrl.$path, $payload);
        } catch (ConnectionException $exception) {
            Log::error('Product scraper connection error', [
                'path' => $path,
                'error' => $exception->getMessage(),
            ]);
            throw new ProductScraperException(__('site.scraper_connection_error'));
        }

        $json = $response->json();
        if ($response->successful()) {
            return is_array($json) ? $json : [];
        }

        $message = is_array($json)
            ? ($json['error'] ?? $json['detail'] ?? __('site.scraper_request_failed'))
            : __('site.scraper_request_failed');

        if (is_array($message)) {
            $message = json_encode($message, JSON_UNESCAPED_UNICODE);
        }

        Log::error('Product scraper request failed', [
            'path' => $path,
            'status' => $response->status(),
            'body' => $json ?? $response->body(),
        ]);

        throw new ProductScraperException((string) $message, $response->status());
    }
}
