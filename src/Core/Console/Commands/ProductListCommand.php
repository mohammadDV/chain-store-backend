<?php

namespace Core\Console\Commands;

use Domain\Product\Services\OxylabsService;
use Illuminate\Console\Command;
use Core\Console\Commands\Traits\RequestTrait;
use Domain\Brand\Models\Brand;
use Domain\Product\Models\Category;
use Domain\Product\Models\Endpoint;

class ProductListCommand extends Command
{
    use RequestTrait;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'get-data:product-list {brand_id} {category_id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch products list from Oxylabs';

    protected string $url = 'https://www.adidas.com.tr/tr/erkek-giyim-ceket_mont';

    /**
     * Execute the console command.
     */
    public function handle()
    {

        $oxylabsService = new OxylabsService();

        $brandId = $this->argument('brand_id');
        $categoryId = $this->argument('category_id');

        $brand = Brand::find($brandId);
        $category = Category::find($categoryId);

        if (!$brand) {
            $this->error("Brand with ID {$brandId} not found.");
            return 1;
        }

        if (!$category) {
            $this->error("Category with ID {$categoryId} not found.");
            return 1;
        }

        $start = 0;
        $stop = false;

        // Extract URLs to check for existing endpoints
        // $urls = array_column($endpoints, 'url');

        // Get existing URLs from database
        $existingUrls = Endpoint::query()
            ->where('brand_id', $brand->id)
            ->where('category_id', $category->id)
            ->pluck('url')
            ->toArray();

        while(!$stop) {
            // $domain = 'https://www.adidas.com.tr';
            $url = $this->url . '?start=' . $start;

            $filters = $this->retryRequest($oxylabsService, 'productList', $url);

            if(!empty($filters['status']) && $filters['status'] == 2) {
                $this->error("Connection error: " . $filters['error']);
                $stop = true;
                return;
            }

            $endpoints = $this->cleanProducts($filters, $brand, $category);

            $start += 48;
            $this->info("Endpoints: ".count($endpoints));
            // $this->info("Endpoints: ".var_export($endpoints, true));

            // Bulk insert endpoints
            if (!empty($endpoints)) {

                // Filter out existing endpoints
                $newEndpoints = array_filter($endpoints, function($endpoint) use ($existingUrls) {
                    return !in_array($endpoint['url'], $existingUrls);
                });

                if (!empty($newEndpoints)) {
                    Endpoint::upsert(
                        $endpoints,
                        ['url'], // Unique column
                        ['brand_id', 'category_id', 'updated_at'] // Columns to update on duplicate
                    );
                    $this->info("Inserted/Updated " . count($newEndpoints) . " endpoints");
                }
            }
            // dd($newEndpoints);

            if(count($endpoints) < 48) {
                $stop = true;
            }

            sleep(5);

        }

        $this->info("Done: ".$start);

    }

    /**
     * Clean and normalize the Kaufland product data
     *
     * @param   array  $kauflandComProduct The raw product data from API
     * @param   string $domain The domain of the product
     * @throws  Exception If required product data is missing
     */
    private function cleanProducts(array $response, Brand $brand, Category $category): array
    {
        // Extract product content from results
        $content = $response['results'][0]['content'] ?? null;

        if (!$content) {
            throw new \Exception('Invalid product data structure');
        }

        // Extract and validate title
        $productTitle = $content['title'] ?? null;
        if (!$productTitle) {
            throw new \Exception('Product not found');
        }
        $links = [];

        foreach ($content['products'] as $product) {

            $url = $this->extractLink($product ?? '');
            if(!empty($url)) {

                $links[] = [
                    'url' => $brand->domain . $url,
                    'brand_id' => $brand->id,
                    'category_id' => $category->id,
                    'status' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        // Return normalized product data
        return $links;
    }

    /**
     * Extract first href link from HTML
     *
     * @param  ?string  $html HTML content from API
     * @return string First href link found
     */
    private function extractLink(?string $html): string
    {
        if (empty($html)) {
            return "";
        }

        // Extract the first href attribute from anchor tags
        // Pattern matches: <a href="/tr/daily-4.0-ayakkabi/IF4492.html" ...>
        if (preg_match('/<a[^>]+href=["\'](\/[^"\']+)["\']/i', $html, $matches)) {
            return $matches[1];
        }

        // If no href found, return empty string
        return "";
    }


}
