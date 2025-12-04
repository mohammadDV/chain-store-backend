<?php

namespace Core\Console\Commands;

use Domain\Product\Models\Product;
use Domain\Product\Services\OxylabsService;
use Illuminate\Console\Command;

class ProductCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'get-data:product';

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

        // $url = 'https://www.adidas.com.tr/tr/copa-mundial-cim-saha-kramponu/JP6693.html';
        // $url = 'https://www.adidas.com.tr/tr/essentials-3-stripes-french-terry-sweatshirt/JE6372.html';
        // $this->url = 'https://www.adidas.com.tr/tr/stan-smith-shoes/FX5499.html';
        // $this->url = 'https://www.adidas.com.tr/tr/adicolor-3-stripes-sprinter-sort/JD3119.html';
        $this->url = 'https://www.adidas.com.tr/tr/adidas-disney-minnie-mouse-cocuk-tayti/JL9192.html';


        $filters = $this->retryRequest($oxylabsService);

        if(!empty($filters['status']) && $filters['status'] == 2) {
            $this->error("Connection error: " . $filters['error']);
            return;
        }

        $productData = $this->cleanProductData($filters, $this->domain);

        // dd($productData);

        $this->storeProduct($productData);

        $this->info("Products: " . var_export($productData, true));


    }

    private function retryRequest(OxylabsService $oxylabsService, int $attempt = 1): array
    {
        $this->info("URL: " . $this->url);
        $this->info("Attempt: " . $attempt);
        $this->info("--------------------------------");

        $filters = $oxylabsService->fetchRequest('product', $this->url);

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

    private function storeProduct(array $productData): Product {

        $product = Product::updateOrCreate([
            'url' => $this->url,
        ], [
            'title' => $productData['title'],
            'description' => $productData['explanation'],
            'details' => $productData['details'],
            'amount' => $productData['price'],
            'discount' => $productData['discount'],
            'image' => $productData['images'][0] ?? null,
            'status' => Product::PENDING,
            'stock' => 10,
            'vip' => false,
            'priority' => 1,
            'color_id' => 1,
            'category_id' => 9,
            'brand_id' => 1,
            'user_id' => 1,
            'related_products' => !empty($productData['related_products']) ? json_encode($productData['related_products']) : null,
        ]);

        // Sync images: only add/remove what's needed
        if (!empty($productData['images'])) {

            // Create or update images
            foreach ($productData['images'] as $key => $imagePath) {
                $product->files()->updateOrCreate(
                    [
                        'path' => $imagePath,
                        'type' => 'image',
                    ],
                    [
                        'status' => 1,
                        'priority' => 10 - $key,
                    ]
                );
            }
        }

        // Sync sizes: only add/remove what's needed
        if (!empty($productData['size'])) {

            // Create or update sizes
            foreach ($productData['size'] as $key => $sizeTitle) {
                $product->sizes()->updateOrCreate(
                    ['code' => trim($sizeTitle)],
                    [
                        'title' => trim($sizeTitle),
                        'status' => 1,
                        'stock' => 5,
                        'priority' => 100 - $key,
                    ]
                );
            }
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
            throw new \Exception('Invalid product data structure');
        }

        // Extract and validate title
        $productTitle = $this->extractTitle($content['title'][0] ?? null);
        if (!$productTitle) {
            throw new \Exception('Product not found');
        }
        $productExplanation = $this->extractTextContent($content['explanation'] ?? null);
        $productDetails = $this->extractTextContent($content['details'] ?? null);
        $productImages = $this->extractImages($content['images'] ?? null);
        $productRelatedProducts = $this->extractRelatedProducts($content['related_products'] ?? null);
        $productSize = $this->extractSize($content['size'] ?? null);
        $productPrice = $this->extractPrice($content['price'][1] ?? null);
        $productDiscount = $this->extractDiscount($content['discount'][0] ?? null);

        // Return normalized product data
        return [
            'title' => $productTitle,
            'explanation' => $productExplanation,
            'details' => $productDetails,
            'images' => $productImages,
            'size' => $productSize,
            'price' => $productPrice,
            'discount' => $productDiscount,
            'related_products' => $productRelatedProducts,
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

    private function extractRelatedProducts(?array $array): array
    {
        if (empty($array)) {
            return [];
        }

        $images = [];

        foreach ($array as $item) {
            // Pattern matches: <a href="/tr/stan-smith-ayakkabi/M20324.html" aria-current="false"><span class="color-variation_out-of-stock-diagonal-line__UZmpY"><svg width="100%" height="100%" fill="none" xmlns="http://www.w3.org/2000/svg" style="top:0;position:absolute"><rect x="0" y="2" width="100%" transform="" fill="black" stroke="#ECEFF1" stroke-width="2" height="3"/></svg></span><img src="https://assets.adidas.com/images/e_trim:EAEEEF/c_lpad,w_iw,h_ih/b_rgb:EAEEEF/w_180,f_auto,q_auto,fl_lossy,c_fill,g_auto/25c70a990dd74210aa47a59900ebfe5d_9366/Stan_Smith_Ayakkabi_Beyaz_M20324_00_plp_standard.jpg" alt="&#220;r&#252;n rengi: Cloud White / Core White / Green"/></a>
            preg_match('/<a[^>]+href=["\'](https:\/\/[^"\']+|(?:\/[^"\']+))["\']/i', $item, $matches);

            if(!empty($matches[1])) {
                $url = $matches[1];
                // Convert relative URLs to absolute URLs
                if (strpos($url, '/') === 0 && strpos($url, '//') !== 0) {
                    $url = $this->domain . $url;
                }
                $images[] = $url;
            }
        }

        return $images;
    }

    private function extractImages(?array $array): array
    {
        if (empty($array)) {
            return [];
        }

        $images = [];

        foreach ($array as $item) {
            // Pattern matches: <picture data-testid="pdp-gallery-picture"><source srcset="data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==" media="(max-width: 959)"><img src="https://assets.adidas.com/images/w_600,f_auto,q_auto/8f2204188e544933ab681c1a847f8387_9366/Copa_Mundial_Cim_Saha_Kramponu_Siyah_JP6693_22_model.jpg" srcset="https://assets.adidas.com/images/h_320,f_auto,q_auto,fl_lossy,c_fill,g_auto/8f2204188e544933ab681c1a847f8387_9366/Copa_Mundial_Cim_Saha_Kramponu_Siyah_JP6693_22_model.jpg 320w, https://assets.adidas.com/images/h_420,f_auto,q_auto,fl_lossy,c_fill,g_auto/8f2204188e544933ab681c1a847f8387_9366/Copa_Mundial_Cim_Saha_Kramponu_Siyah_JP6693_22_model.jpg 420w, https://assets.adidas.com/images/h_600,f_auto,q_auto,fl_lossy,c_fill,g_auto/8f2204188e544933ab681c1a847f8387_9366/Copa_Mundial_Cim_Saha_Kramponu_Siyah_JP6693_22_model.jpg 600w, https://assets.adidas.com/images/h_640,f_auto,q_auto,fl_lossy,c_fill,g_auto/8f2204188e544933ab681c1a847f8387_9366/Copa_Mundial_Cim_Saha_Kramponu_Siyah_JP6693_22_model.jpg 640w, https://assets.adidas.com/images/h_840,f_auto,q_auto,fl_lossy,c_fill,g_auto/8f2204188e544933ab681c1a847f8387_9366/Copa_Mundial_Cim_Saha_Kramponu_Siyah_JP6693_22_model.jpg 840w" sizes="(max-width: 320px) 320px, (max-width: 420px) 420px, (max-width: 600px) 600px, (max-width: 640px) 640px, (max-width: 840px) 840px" alt="Siyah Copa Mundial &#199;im Saha Kramponu" data-inject_ssr_performance_instrument=""/></source></picture>
            preg_match('/<img[^>]+src=["\'](https:\/\/[^"\']+)["\']/i', $item, $matches);

            if(!empty($matches[1])) {
                $images[] = $matches[1];
            }
        }

        return $images;
    }

    /**
     * Extract clean text content from HTML
     *
     * @param string|null $html The HTML content to clean
     * @return string Clean text content
     */
    private function extractTextContent(?string $html): string
    {
        if (empty($html)) {
            return '';
        }

        // Decode HTML entities first
        $html = html_entity_decode($html, ENT_QUOTES, 'UTF-8');

        // Add line breaks before list items for better formatting
        $html = preg_replace('/<li[^>]*>/i', "\n• ", $html);

        // Remove all HTML tags
        $text = strip_tags($html);

        // Clean up extra whitespace and normalize line breaks
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);

        return $text;
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
