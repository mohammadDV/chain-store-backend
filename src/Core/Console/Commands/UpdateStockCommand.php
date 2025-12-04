<?php

namespace Core\Console\Commands;

use Domain\Product\Models\Product;
use Domain\Product\Models\Size;
use Domain\Product\Services\OxylabsService;
use Illuminate\Console\Command;

class UpdateStockCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'get-data:update-stock';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch product from Oxylabs';

    protected int $categoryId = 9;
    protected ?string $url = null;
    protected ?string $domain = 'https://www.adidas.com.tr';


    /**
     * Execute the console command.
     */
    public function handle()
    {

        $oxylabsService = new OxylabsService();

        $size = Size::find(48);
        $product = Product::find($size->product_id);

        // $url = 'https://www.adidas.com.tr/tr/copa-mundial-cim-saha-kramponu/JP6693.html';
        // $url = 'https://www.adidas.com.tr/tr/essentials-3-stripes-french-terry-sweatshirt/JE6372.html';
        // $this->url = 'https://www.adidas.com.tr/tr/stan-smith-shoes/FX5499.html?forceSelSize=36';
        $this->url = $product->url . '?forceSelSize=' . str_replace(' ', '+', $size->code);


        $filters = $this->retryRequest($oxylabsService);

        if(!empty($filters['status']) && $filters['status'] == 2) {
            $this->error("Connection error: " . $filters['error']);
            return;
        }

        $productData = $this->cleanProductData($filters, $this->domain);

        $this->updateProduct($productData, $product, $size);

        // $this->info("Products: " . var_export($filters, true));
        $this->info("Products: " . var_export($productData, true));


    }

    private function retryRequest(OxylabsService $oxylabsService, int $attempt = 1): array
    {
        $this->info("URL: " . $this->url);
        $this->info("Attempt: " . $attempt);
        $this->info("--------------------------------");

        $filters = $oxylabsService->fetchRequest('update_stock', $this->url);

        if(!empty($filters['status']) && $filters['status'] == 2) {
            if($attempt >= 3) {
                $this->error("Connection error after 3 attempts: " . $filters['error']);
                return $filters;
            }

            $this->error("Connection error (attempt {$attempt}/3): " . $filters['error']);
            sleep(20);
            return $this->retryRequest($oxylabsService, $attempt + 1);
        }

        return $filters;

    }

    private function updateProduct(array $productData, Product $product, Size $size): Product {

        $product->update([
            'amount' => $productData['price'],
            'discount' => $productData['discount'],
        ]);

        $size->update([
            'stock' => $productData['stock'] == 'notfound' ? 0 : $productData['stock'],
        ]);

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
            throw new \Exception('Invalid product data structure');
        }

        // Extract and validate title
        $productTitle = $this->extractTitle($content['title'][0] ?? null);
        if (!$productTitle) {
            throw new \Exception('Product not found');
        }

        $productStock = $this->extractStock($content['stock'][0] ?? null);
        $productPrice = $this->extractPrice($content['price'][1] ?? null);
        $productDiscount = $this->extractDiscount($content['discount'][0] ?? null);

        // Return normalized product data
        return [
            'title' => $productTitle,
            'stock' => $productStock,
            'price' => $productPrice,
            'discount' => $productDiscount,
        ];
    }

    private function extractDiscount(?string $price): int
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

    private function extractStock(?string $string): string
    {
        $this->info("String: " . $string);
        if (empty($string)) {
            $this->info("Stock is empty");
            return 5;
        }

        $stock = 0;

        // Pattern matches:div class="scarcity-message_scarcity-message__7X5BG" data-auto-id="scarcity-message" aria-live="polite" role="status">Stokta yaln&#305;zca 2 adet kald&#305;</div>
        preg_match('/<div[^>]*>([^<]+)<\/div>/i', $string, $matches);
        if(!empty($matches[1])) {
            $content = html_entity_decode($matches[1], ENT_QUOTES, 'UTF-8');
            $this->info("Content: " . $content);
            if (trim(strtolower($content)) == 'tükenmek üzere') {
                return 'notfound';
            }
            // Extract number from the content (e.g., "Stokta yalnızca 2 adet kaldı" -> 2)
            preg_match('/\d+/', $content, $numberMatches);
            $this->info("Numbers matches: " . var_export($numberMatches, true));
            if(!empty($numberMatches[0])) {
                $this->info("---Number matches: " . $numberMatches[0]);
                $stock = (int) $numberMatches[0];
            }
        }

        return $stock;
    }

    /**
     * Extract clean text content from HTML
     *
     * @param string|null $html The HTML content to clean
     * @return string Clean text content
     */
    private function extractTitle(?string $html): string
    {
        if (empty($html)) {
            return '';
        }

        // Decode HTML entities first
        $html = html_entity_decode($html, ENT_QUOTES, 'UTF-8');

        // Remove all HTML tags
        $text = strip_tags($html);
        $text = trim($text);

        return $text;
    }


}
