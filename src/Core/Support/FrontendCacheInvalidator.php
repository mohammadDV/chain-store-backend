<?php

namespace Core\Support;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class FrontendCacheInvalidator
{
    /**
     * Ask the Next.js app to drop the given cache tags.
     *
     * @param  list<string>  $tags
     */
    public function revalidate(array $tags): void
    {
        $tags = array_values(array_filter($tags));
        if ($tags === []) {
            return;
        }

        $baseUrl = rtrim((string) config('app.frontend_url'), '/');
        $secret = (string) config('app.revalidate_secret');

        if ($baseUrl === '' || $secret === '') {
            return;
        }

        try {
            Http::timeout(3)
                ->acceptJson()
                ->post($baseUrl.'/api/revalidate', [
                    'secret' => $secret,
                    'tags' => $tags,
                ]);
        } catch (Throwable $e) {
            Log::warning('Frontend cache revalidation failed', [
                'tags' => $tags,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
