<?php

namespace Domain\Seo\Concerns;

use Domain\Seo\Models\SeoRedirect;

trait RecordsSlugRedirects
{
    public static function bootRecordsSlugRedirects(): void
    {
        static::updating(function (self $model): void {
            if (! $model->isDirty('slug')) {
                return;
            }

            $oldSlug = $model->getOriginal('slug');
            $newSlug = $model->slug;

            if (! is_string($oldSlug) || $oldSlug === '' || ! is_string($newSlug) || $newSlug === '') {
                return;
            }

            if ($oldSlug === $newSlug) {
                return;
            }

            $fromPath = $model->seoFrontendPath($oldSlug);
            $toPath = $model->seoFrontendPath($newSlug);

            SeoRedirect::query()->updateOrCreate(
                ['from_path' => $fromPath],
                [
                    'to_path' => $toPath,
                    'status_code' => 301,
                ]
            );

            // Avoid redirect chains: anything pointing at the old path should now point at the new one.
            SeoRedirect::query()
                ->where('to_path', $fromPath)
                ->update(['to_path' => $toPath]);
        });
    }

    /**
     * Frontend path for this entity with the given slug.
     */
    abstract public function seoFrontendPath(string $slug): string;
}
