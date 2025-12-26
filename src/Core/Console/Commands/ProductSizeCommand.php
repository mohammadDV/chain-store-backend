<?php

namespace Core\Console\Commands;

use Core\Console\Commands\Traits\RequestTrait;
use Domain\Product\Models\Product;
use Domain\Product\Models\Size;
use Domain\Product\Services\OxylabsService;
use Illuminate\Console\Command;

class ProductSizeCommand extends Command
{
    use RequestTrait;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update-data:adidas-product-size {--limit=10 : Limit the number of endpoints to process} {--failed=0 : Failed products} {--all=0 : Failed products}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'update product sizes when the products does not have sizes and for compleleting the product sizes';


    /**
     * Execute the console command.
     */
    public function handle()
    {

        $oxylabsService = new OxylabsService();

        $limit = (int) $this->option('limit');
        $isFailed = (int) $this->option('failed');
        $isAll = (int) $this->option('all');

        Size::query()
            ->where('code', 'AAA')
            ->delete();

        $endpoints = Product::query()
            ->with('sizes')
            ->with('brand')
            ->where('brand_id', 1)
            ->whereNotNull('url')
            ->where(function($query) use ($isFailed) {
                $query->where('is_failed', $isFailed);
            })
            ->when($isAll == 0, function($query) {
                $query->whereDoesntHave('sizes');
            })
            ->orderBy('updated_at', 'asc')
            ->limit($limit)
            ->get();

        $count = 0;
        foreach($endpoints as $endpoint) {

            $filters = $this->retryRequest($oxylabsService, 'adidas_product_size', $endpoint->url, 1, $endpoint?->brand?->domain);
            if(!empty($filters['status']) && in_array($filters['status'], [4, 2])) {
                continue;
            }

            $productData = $this->cleanProductData($filters);

            if(!empty($productData['status']) && $productData['status'] == 2) {
                continue;
            }

            if(!empty($productData['status']) && $productData['status'] == 3) {

                $endpoint->update([
                    'is_failed' => 1,
                    'updated_at' => now(),
                ]);
                $this->error("Failed to get product sizesssssssssss: " . $endpoint->url);
                continue;
            }

            $product = $this->storeProduct($productData, $endpoint);

            if (!empty($product?->id)) {
                $count++;
                $this->info("Products: " . $endpoint->url);
                $this->info("Products: " . $product->id);
            }
            sleep(2);
        }

        $this->info("Done: " . $count);
    }

    private function storeProduct(array $productData, Product $product): Product {

        $product->update([
            'amount' => $productData['price'],
            'discount' => $productData['discount'],
            'is_failed' => 0,
            'updated_at' => now(),
        ]);

        // Create or update sizes
        foreach ($productData['size'] ?? [] as $key => $sizeTitle) {
            $product->sizes()->updateOrCreate(
                ['code' => trim($sizeTitle)],
                [
                    'title' => trim($sizeTitle),
                    'status' => 1,
                    'priority' => 100 - $key,
                ]
            );
        }

        return $product;
    }

    /**
     * Clean and normalize the Kaufland product data
     *
     * @param   array  $kauflandComProduct The raw product data from API
     * @param   string $domain The domain of the product
     * @throws  Exception If required product data is missing
     */
    private function cleanProductData(array $response): array
    {
        // Extract product content from results
        $content = $response['results'][0]['content'] ?? null;

        if (!$content) {
            return ['status' => 2];
        }

        if (empty($content['size']) || empty($content['price'][1])) {
            return ['status' => 3];
        }

        if (in_array('<span>AAA</span>', $content['size'])) {
            return ['status' => 3];
        }

        $productSize = $this->extractSize($content['size'] ?? null);
        $productPrice = $this->extractPrice($content['price'][1] ?? null);
        $productDiscount = $this->extractDiscount($content['discount'] ?? null);

        // Return normalized product data
        return [
            'size' => $productSize,
            'price' => $productPrice,
            'discount' => $productDiscount,
        ];
    }

    private function extractDiscount(?string $price): int
    {
        if (empty($price)) {
            return 0;
        }

        // Pattern matches: <span data-testid="discount-text" class="_discountText_1dnvn_90">-40%<span class="_visuallyHidden_1dnvn_2">&#304;ndirim</span></span>
        preg_match('/<span[^>]*data-testid="discount-text"[^>]*>(-?\d+)%<span/i', $price, $matches);
        if(!empty($matches[1])) {
            // Extract the number from the discount percentage (e.g., 40 from "-40%")
            $discountNumber = abs((int) $matches[1]);
            return $discountNumber;
        }
        return 0;
    }

    private function extractPrice(?string $price): int
    {
        if (empty($price)) {
            return 0;
        }

        // Pattern matches: <span>1.499,00 TL</span> or <span>5.399 TL</span>
        // <span class="_sale-color_1dnvn_101">2.099 TL</span>
        preg_match('/<span[^>]*>([^<]+)<\/span>/i', $price, $matches);
        if(!empty($matches[1])) {
            $priceString = $matches[1];

            // Remove "TL" text
            $priceString = preg_replace('/\s*TL\s*/i', '', $priceString);

            // Remove thousands separator (period)
            $priceString = str_replace('.', '', $priceString);

            // Remove decimal separator (comma) and everything after it if present
            if (strpos($priceString, ',') !== false) {
                $priceString = strstr($priceString, ',', true);
            }

            // Convert to integer
            return (int) $priceString;
        }
        return 0;
    }

    private function extractSize(?array $array): array
    {
        if (empty($array)) {
            return [];
        }

        $sizes = [];

        foreach ($array as $item) {
            // Pattern matches:<span>40 2/3</span>
            // Pattern matches:<span>41 1/3</span>
            // Pattern matches:<span>42</span>
            // Also handles: <span class="...">42</span>
            preg_match('/<span[^>]*>([^<]+)<\/span>/i', $item, $matches);
            if(!empty($matches[1])) {
                // Decode HTML entities to properly handle Turkish characters (e.g., Ş from &#350;)
                $sizeTitle = html_entity_decode($matches[1], ENT_QUOTES, 'UTF-8');
                $sizes[] = $sizeTitle;
            }
        }

        return $sizes;
    }


}