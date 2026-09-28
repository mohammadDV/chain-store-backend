<?php

namespace Core\Console\Commands;

use Core\Helpers\HelperClass;
use Domain\Product\Models\Category;
use Domain\Product\Models\Product;
use Domain\Post\Models\Post;
use Illuminate\Console\Command;

class BackfillSeoSlugsCommand extends Command
{
    protected $signature = 'seo:backfill-slugs';

    protected $description = 'Backfill missing SEO slugs for products, categories, and posts';

    public function handle(): int
    {
        $this->backfill(Product::class, 'products');
        $this->backfill(Category::class, 'categories');
        $this->backfill(Post::class, 'posts');

        $this->info('SEO slug backfill completed.');

        return self::SUCCESS;
    }

    /**
     * @param  class-string<\Illuminate\Database\Eloquent\Model>  $modelClass
     */
    private function backfill(string $modelClass, string $label): void
    {
        $count = 0;

        $modelClass::query()
            ->where(function ($query) {
                $query->whereNull('slug')->orWhere('slug', '');
            })
            ->orderBy('id')
            ->chunkById(100, function ($items) use (&$count, $modelClass) {
                foreach ($items as $item) {
                    $base = HelperClass::sluggableCustomSlugMethod((string) $item->title) ?: 'item-'.$item->id;
                    $slug = $base;
                    $i = 1;

                    while (
                        $modelClass::query()
                            ->where('slug', $slug)
                            ->where('id', '!=', $item->id)
                            ->exists()
                    ) {
                        $slug = $base.'-'.$i;
                        $i++;
                    }

                    // Bypass observers / sluggable events for a direct write.
                    $modelClass::query()->whereKey($item->id)->update(['slug' => $slug]);
                    $count++;
                }
            });

        $this->info("Backfilled {$count} {$label}.");
    }
}
