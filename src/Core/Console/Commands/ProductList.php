<?php

namespace Core\Console\Commands;

use Domain\Product\Services\OxylabsService;
use Illuminate\Console\Command;

class ProductList extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'get-data:product-list';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch products list from Oxylabs';

    /**
     * Execute the console command.
     */
    public function handle()
    {

        $oxylabsService = new OxylabsService();

        $start = 0;
        $stop = false;

        while(!$stop) {
            $domain = 'https://www.adidas.com.tr';
            $url = 'https://www.adidas.com.tr/tr/erkek-yeni_urunler?start=' . $start;

            $filters = $oxylabsService->fetchRequest('productList', $url);

            $products = $this->cleanProducts($filters, $domain);

            $start += 48;
            $this->info("Done: ".$start);
            $this->info("Products: ".count($products));

            if(count($products) < 48) {
                $stop = true;
            }

            sleep(5);

        }

    }

    /**
     * Clean and normalize the Kaufland product data
     *
     * @param   array  $kauflandComProduct The raw product data from API
     * @param   string $domain The domain of the product
     * @throws  Exception If required product data is missing
     */
    private function cleanProducts(array $response, string $domain): array
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
            $links[] = $domain . $this->extractLink($product ?? '');
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
