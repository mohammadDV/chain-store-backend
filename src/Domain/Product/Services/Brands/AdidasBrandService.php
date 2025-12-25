<?php

namespace Domain\Product\Services\Brands;

use Domain\Brand\Models\Brand;
use Domain\Product\Models\Category;
use Domain\Product\Models\Product;
use Domain\Product\Models\Size;

/**
 * Adidas brand-specific product scraping service
 *
 * Handles all Adidas-specific extraction logic for products and product lists
 */
class AdidasBrandService implements BrandServiceInterface
{
    /**
     * {@inheritDoc}
     */
    public function cleanProductData(array $response, string $domain): array
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

        $productImages = $this->extractImages($content['images'] ?? null);
        $productRelatedProducts = $this->extractRelatedProducts($content['related_products'] ?? null, $domain);
        $productSize = $this->extractSize($content['size'] ?? null);
        $productPrice = $this->extractPrice($content['price'][1] ?? null);
        $productDiscount = $this->extractDiscount($content['discount'] ?? null);

        // Return normalized product data
        return [
            'title' => $productTitle,
            'images' => $productImages,
            'size' => $productSize,
            'price' => $productPrice,
            'discount' => $productDiscount,
            'related_products' => $productRelatedProducts,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function cleanProductList(array $response, Brand $brand, Category $category): array
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
            if (!empty($url)) {
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
     * Clean and normalize stock data from API response
     *
     * @param array $response The raw response from Oxylabs API
     * @param string $domain The brand domain (e.g., 'https://www.adidas.com.tr')
     * @param string $sizeCode The size code
     * @return array Normalized stock data with keys: stock
     * @throws \Exception If required stock data is missing
     */
    public function cleanStockData(array $response, string $domain, string $sizeCode): array
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

        $productSize = $this->extractSize($content['size'] ?? null);
        $productStock = 0;
        if (in_array($sizeCode, $productSize)) {
            $productStock = $this->extractStock($content['stock'][0] ?? null);
        }
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

    /**
     * {@inheritDoc}
     */
    public function getProductParsingKey(): string
    {
        return 'adidas_product';
    }

    /**
     * {@inheritDoc}
     */
    public function getProductListParsingKey(): string
    {
        return 'adidas_productList';
    }

    /**
     * {@inheritDoc}
     */
    public function getUpdateStockParsingKey(): string
    {
        return 'adidas_update_stock';
    }

    /**
     * Extract discount percentage from HTML
     *
     * @param string|null $price HTML content containing discount information
     * @return int Discount percentage (e.g., 40 for "-40%")
     */
    private function extractDiscount(?string $price): int
    {
        if (empty($price)) {
            return 0;
        }

        // Pattern matches: <span data-testid="discount-text" class="_discountText_1dnvn_90">-40%<span class="_visuallyHidden_1dnvn_2">&#304;ndirim</span></span>
        preg_match('/<span[^>]*data-testid="discount-text"[^>]*>(-?\d+)%<span/i', $price, $matches);
        if (!empty($matches[1])) {
            // Extract the number from the discount percentage (e.g., 40 from "-40%")
            $discountNumber = abs((int) $matches[1]);
            return $discountNumber;
        }
        return 0;
    }

    /**
     * Extract price from HTML
     *
     * @param string|null $price HTML content containing price information
     * @return int Price in smallest currency unit (e.g., kuruş for TL)
     */
    private function extractPrice(?string $price): int
    {
        if (empty($price)) {
            return 0;
        }

        // Pattern matches: <span>1.499,00 TL</span> or <span>5.399 TL</span>
        // <span class="_sale-color_1dnvn_101">2.099 TL</span>
        preg_match('/<span[^>]*>([^<]+)<\/span>/i', $price, $matches);
        if (!empty($matches[1])) {
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

    /**
     * Extract sizes from HTML array
     *
     * @param array|null $array Array of HTML strings containing size information
     * @return array Array of size strings
     */
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
            if (!empty($matches[1])) {
                // Decode HTML entities to properly handle Turkish characters (e.g., Ş from &#350;)
                $sizeTitle = html_entity_decode($matches[1], ENT_QUOTES, 'UTF-8');
                $sizes[] = $sizeTitle;
            }
        }

        return $sizes;
    }

    /**
     * Extract related product URLs from HTML array
     *
     * @param array|null $array Array of HTML strings containing related product links
     * @param string $domain The brand domain to convert relative URLs to absolute
     * @return array Array of related product URLs
     */
    private function extractRelatedProducts(?array $array, string $domain): array
    {
        if (empty($array)) {
            return [];
        }

        $images = [];

        foreach ($array as $item) {
            // Pattern matches: <a href="/tr/stan-smith-ayakkabi/M20324.html" aria-current="false"><span class="color-variation_out-of-stock-diagonal-line__UZmpY"><svg width="100%" height="100%" fill="none" xmlns="http://www.w3.org/2000/svg" style="top:0;position:absolute"><rect x="0" y="2" width="100%" transform="" fill="black" stroke="#ECEFF1" stroke-width="2" height="3"/></svg></span><img src="https://assets.adidas.com/images/e_trim:EAEEEF/c_lpad,w_iw,h_ih/b_rgb:EAEEEF/w_180,f_auto,q_auto,fl_lossy,c_fill,g_auto/25c70a990dd74210aa47a59900ebfe5d_9366/Stan_Smith_Ayakkabi_Beyaz_M20324_00_plp_standard.jpg" alt="&#220;r&#252;n rengi: Cloud White / Core White / Green"/></a>
            preg_match('/<a[^>]+href=["\'](https:\/\/[^"\']+|(?:\/[^"\']+))["\']/i', $item, $matches);

            if (!empty($matches[1])) {
                $url = $matches[1];
                // Convert relative URLs to absolute URLs
                if (strpos($url, '/') === 0 && strpos($url, '//') !== 0) {
                    $url = $domain . $url;
                }
                $images[] = $url;
            }
        }

        return $images;
    }

    /**
     * Extract image URLs from HTML array
     *
     * @param array|null $array Array of HTML strings containing image information
     * @return array Array of image URLs
     */
    private function extractImages(?array $array): array
    {
        if (empty($array)) {
            return [];
        }

        $images = [];

        foreach ($array as $item) {
            // Pattern matches: <picture data-testid="pdp-gallery-picture"><source srcset="data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==" media="(max-width: 959)"><img src="https://assets.adidas.com/images/w_600,f_auto,q_auto/8f2204188e544933ab681c1a847f8387_9366/Copa_Mundial_Cim_Saha_Kramponu_Siyah_JP6693_22_model.jpg" srcset="https://assets.adidas.com/images/h_320,f_auto,q_auto,fl_lossy,c_fill,g_auto/8f2204188e544933ab681c1a847f8387_9366/Copa_Mundial_Cim_Saha_Kramponu_Siyah_JP6693_22_model.jpg 320w, https://assets.adidas.com/images/h_420,f_auto,q_auto,fl_lossy,c_fill,g_auto/8f2204188e544933ab681c1a847f8387_9366/Copa_Mundial_Cim_Saha_Kramponu_Siyah_JP6693_22_model.jpg 420w, https://assets.adidas.com/images/h_600,f_auto,q_auto,fl_lossy,c_fill,g_auto/8f2204188e544933ab681c1a847f8387_9366/Copa_Mundial_Cim_Saha_Kramponu_Siyah_JP6693_22_model.jpg 600w, https://assets.adidas.com/images/h_640,f_auto,q_auto,fl_lossy,c_fill,g_auto/8f2204188e544933ab681c1a847f8387_9366/Copa_Mundial_Cim_Saha_Kramponu_Siyah_JP6693_22_model.jpg 640w, https://assets.adidas.com/images/h_840,f_auto,q_auto,fl_lossy,c_fill,g_auto/8f2204188e544933ab681c1a847f8387_9366/Copa_Mundial_Cim_Saha_Kramponu_Siyah_JP6693_22_model.jpg 840w" sizes="(max-width: 320px) 320px, (max-width: 420px) 420px, (max-width: 600px) 600px, (max-width: 640px) 640px, (max-width: 840px) 840px" alt="Siyah Copa Mundial &#199;im Saha Kramponu" data-inject_ssr_performance_instrument=""/></source></picture>
            preg_match('/<img[^>]+src=["\'](https:\/\/[^"\']+)["\']/i', $item, $matches);

            if (!empty($matches[1])) {
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

    /**
     * Extract first href link from HTML
     *
     * @param string|null $html HTML content from API
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

    /**
     * Store product data in the database
     *
     * @param array $productData Product data to store
     * @param int $categoryId Category ID
     * @param string $url Product URL
     * @param int $brandId Brand ID
     * @return Product Product model
     */
    public function storeProduct(array $productData, int $categoryId, string $url, int $brandId): Product {

        $product = Product::updateOrCreate([
            'url' => $url,
        ], [
            'title' => $productData['title'],
            'description' => '',
            'details' => '',
            'amount' => $productData['price'],
            'discount' => $productData['discount'],
            'image' => $productData['images'][0] ?? null,
            'status' => Product::PENDING,
            'stock' => config('product.default_stock'),
            'vip' => false,
            'priority' => 1,
            'color_id' => 1,
            'brand_id' => $brandId,
            'user_id' => 1,
            'related_products' => !empty($productData['related_products']) ? json_encode($productData['related_products']) : null,
        ]);

        // Sync categories using the many-to-many relationship
        $product->categories()->sync([$categoryId]);

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
                        'stock' => config('product.default_stock'),
                        'priority' => 100 - $key,
                    ]
                );
            }
        }

        return $product;
    }

    /**
     * Extract clean text content from HTML
     *
     * @param string|null $html The HTML content to clean
     * @return string Clean text content
     */
    public function extractTextContent(?string $html): string
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
     * Extract stock from HTML
     *
     * @param string|null $string The HTML content to extract stock from
     * @return string|int Stock value or 'notfound' if stock is not found
     */
    public function extractStock(?string $string): string|int
    {
        if (empty($string)) {
            return config('product.default_stock');
        }

        $stock = 0;
        // Pattern matches:div class="scarcity-message_scarcity-message__7X5BG" data-auto-id="scarcity-message" aria-live="polite" role="status">Stokta yaln&#305;zca 2 adet kald&#305;</div>
        preg_match('/<div[^>]*>([^<]+)<\/div>/i', $string, $matches);
        if(!empty($matches[1])) {
            $content = html_entity_decode($matches[1], ENT_QUOTES, 'UTF-8');
            if (trim(strtolower($content)) == 'tükenmek üzere') {
                return 'notfound';
            }
            // Extract number from the content (e.g., "Stokta yalnızca 2 adet kaldı" -> 2)
            preg_match('/\d+/', $content, $numberMatches);
            if(!empty($numberMatches[0])) {
                return (int) $numberMatches[0];
            }
        }

        return $stock;
    }

    /**
     * Update product data in the database
     *
     * @param array $productData Product data to update
     * @param Product $product Product model
     * @param Size $size Size model
     * @return Product Product model
     */
    public function updateProduct(array $productData, Product $product, Size $size): Product {

        $product->update([
            'amount' => $productData['price'],
            'discount' => $productData['discount'],
        ]);

        $size->update([
            'stock' => $productData['stock'] == 'notfound' ? 0 : $productData['stock'],
            'updated_at' => now(),
        ]);

        return $product;
    }
}