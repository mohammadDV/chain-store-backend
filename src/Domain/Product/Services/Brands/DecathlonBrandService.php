<?php

namespace Domain\Product\Services\Brands;

use Domain\Brand\Models\Brand;
use Domain\Product\Models\Category;
use Domain\Product\Models\Endpoint;
use Domain\Product\Models\Product;

/**
 * Decathlon brand-specific product scraping service
 *
 */
class DecathlonBrandService implements BrandServiceInterface
{
    /**
     * {@inheritDoc}
     */
    public function cleanProductData(array $response, string $domain): array
    {
        // TODO: Implement Decathlon product data extraction
        // Extract product content from results
        $content = $response['results'][0]['content'] ?? null;

        if (!$content) {
            throw new \Exception('Invalid product data structure');
        }

        // TODO: Implement Decathlon-specific extraction methods
        $productTitle = $this->extractTitle($content['title'] ?? null);
        $productPrice = $this->extractPrice($content['price'][0] ?? null);
        $productDiscount = $this->extractDiscount($content['discount'][0] ?? null);
        $productCode = $this->extractCode($content['code'][0] ?? null);
        $productImages = $this->extractImages($content['images'] ?? null);
        $productSize = $this->extractSize($content['size'] ?? null);
        $productRelatedProducts = $this->extractRelatedProducts($content['related_products'] ?? null, $domain);

        // Return normalized product data
        return [
            'title' => $productTitle,
            'images' => $productImages,
            'size' => $productSize,
            'price' => $productPrice,
            'discount' => $productDiscount,
            'code' => $productCode,
            'related_products' => $productRelatedProducts,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function cleanProductList(array $response, Brand $brand, Category $category): array
    {
        // TODO: Implement Decathlon product list extraction
        // Extract product content from results
        $content = $response['results'][0]['content'] ?? null;

        if (!$content) {
            throw new \Exception('Invalid product data structure');
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
        return $links;

        throw new \Exception('DecathlonBrandService::cleanProductList() is not yet implemented');
    }

    /**
     * {@inheritDoc}
     */
    public function getProductParsingKey(): string
    {
        // TODO: Update this if Decathlon needs a different parsing key
        // You may need to add a new key in OxylabsService.php
        return 'decathlon_product';
    }

    /**
     * {@inheritDoc}
     */
    public function getProductListParsingKey(): string
    {
        // TODO: Update this if Decathlon needs a different parsing key
        return 'decathlon_productList';
    }

    // TODO: Implement the following private methods based on Decathlon's HTML structure:

    /**
     * Extract code from HTML
     *
     * @param string|null $code HTML content containing code information
     * @return string Code (e.g., "123456")
     */
    private function extractCode(?string $code): string
    {
        if (empty($code)) {
            return '';
        }

        // Pattern matches: span class="current-selected-model vtmn-block gt-tablet:vtmn-mt-2 vtmn-text-base vtmn-text-content-tertiary vtmn-mt-4">Ref. : 8883162</span>
        preg_match('/<span[^>]*class="[^"]*current-selected-model[^"]*"[^>]*>Ref\.\s*:\s*(\d+)<\/span>/i', $code, $matches);
        if (!empty($matches[1])) {

            return $matches[1];
        }
        return '';
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

        // Pattern matches: <span aria-label="Fiyat indirim oran&#x131;" class="price-discount vtmn-text-content-negative vtmn-text-center vtmn-font-bold vtmn-text-sm vtmn-ml-2 vtmn-leading-4 price-discount-rate">-22%</span>
        preg_match('/<span[^>]*class="[^"]*price-discount-rate[^"]*"[^>]*>(-?\d+)%<\/span>/i', $price, $matches);
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

        // Pattern matches: <span class="vtmn-price vtmn-price_size--large vtmn-price_variant--alert" aria-label="fiyat">&#8378;1.390</span>
        preg_match('/<span[^>]*>([^<]+)<\/span>/i', $price, $matches);
        if (!empty($matches[1])) {
            $priceString = $matches[1];

            // Decode HTML entities (e.g., &#8378; becomes ₺)
            $priceString = html_entity_decode($priceString, ENT_QUOTES, 'UTF-8');

            // Remove currency symbols (₺, TL, etc.)
            $priceString = preg_replace('/[₺\s]*TL\s*/i', '', $priceString);
            $priceString = preg_replace('/[₺€$£¥]/u', '', $priceString);

            // Remove thousands separator (period)
            $priceString = str_replace('.', '', $priceString);

            // Remove decimal separator (comma) and everything after it if present
            if (strpos($priceString, ',') !== false) {
                $priceString = strstr($priceString, ',', true);
            }

            // Remove any remaining non-numeric characters except digits
            $priceString = preg_replace('/[^\d]/', '', $priceString);

            // Convert to integer
            return (int) $priceString;
        }
        return 0;
    }

    /**
     * Extract sizes with stock status from HTML array
     *
     * @param array|null $array Array of HTML strings containing size information
     * @return array Associative array mapping size to stock status (e.g., ["XS" => "inStock", "S" => "low"])
     */
    private function extractSize(?array $array): array
    {
        if (empty($array)) {
            return [];
        }

        $sizes = [];

        foreach ($array as $item) {
            // Pattern matches:<button type="button" class="vtmn-sku-selector__grid-item inStock svelte-fccp9t" id="sku-0" data-index="0" aria-label="Boyut XS , Stokta Mevcut"><div class="vtmn-sku-selector__grid-item-size svelte-fccp9t">XS</div> <div class="vtmn-sku-selector__grid-item-stock sku-selector__stock--inStock svelte-fccp9t">Stokta Mevcut</div>  </button>

            // Extract size from the div with class containing "vtmn-sku-selector__grid-item-size"
            preg_match('/<div[^>]*class="[^"]*vtmn-sku-selector__grid-item-size[^"]*"[^>]*>([^<]+)<\/div>/i', $item, $sizeMatches);

            // Extract stock status from button class (looking for inStock, low, or outOfStock)
            preg_match('/class="[^"]*\b(inStock|low|outOfStock)\b[^"]*"/i', $item, $stockMatches);

            if (!empty($sizeMatches[1])) {
                // Decode HTML entities to properly handle Turkish characters (e.g., Ş from &#350;)
                $size = trim(html_entity_decode($sizeMatches[1], ENT_QUOTES, 'UTF-8'));
                $stockStatus = !empty($stockMatches[1]) ? $stockMatches[1] : 'unknown';
                $sizes[$size] = $stockStatus;
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

        $dataIds = [];

        foreach ($array as $item) {
            // Pattern matches: <button type="button" aria-label="Kan k&#x131;rm&#x131;z&#x131; / vi&#x15F;ne &#xE7;&#xFC;r&#xFC;&#x11F;&#xFC;" data-id="8827917" title="Kan k&#x131;rm&#x131;z&#x131; / vi&#x15F;ne &#xE7;&#xFC;r&#xFC;&#x11F;&#xFC;" aria-current="true" class="variant-list__button vtmn-rounded-200 vtmn-overflow-hidden vtmn-border-border-secondary vtmn-border vtmn-border-solid vtmn-w-full vtmn-h-full svelte-1o2pzmx is-selected">  <img class="vtmn-block vtmn-pointer-events-none svelte-11itto" width="" height="" src="https://contents.mediadecathlon.com/p2957767/k$69eee1548538f90df810917546a65c5f/sq/futbol-uzun-kollu-termal-iclik-kirmizi-unisex-keepdry.jpg?format=auto&amp;f=800x0" srcset="https://contents.mediadecathlon.com/p2957767/k$69eee1548538f90df810917546a65c5f/sq/futbol-uzun-kollu-termal-iclik-kirmizi-unisex-keepdry.jpg?format=auto&amp;f=120x120 120w, https://contents.mediadecathlon.com/p2957767/k$69eee1548538f90df810917546a65c5f/sq/futbol-uzun-kollu-termal-iclik-kirmizi-unisex-keepdry.jpg?format=auto&amp;f=240x240 240w, https://contents.mediadecathlon.com/p2957767/k$69eee1548538f90df810917546a65c5f/sq/futbol-uzun-kollu-termal-iclik-kirmizi-unisex-keepdry.jpg?format=auto&amp;f=360x360 360w" sizes="(min-width: 0px) 120px, 100vw" loading="lazy" aria-hidden="false" alt="Futbol Uzun Kollu Termal &#304;&#231;lik - K&#305;rm&#305;z&#305; - Unisex - Keepdry" data-broken="G&#246;rsel kullan&#305;lam&#305;yor"/> <span class="vtmx-checkbox-circle-fill vtmn-icon-size vtmn-absolute vtmn-top-1/2 vtmn-left-1/2 variant__select-icon vtmn-pointer-events-none svelte-17fg7l5" style="--vtmn-icon-size: 32px;"/></button>
            preg_match('/<button[^>]+data-id=["\'](\d+)["\']/i', $item, $matches);

            if (!empty($matches[1])) {
                $dataIds[] = $matches[1];
            }
        }

        return $dataIds;
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
            // Pattern matches: <img class="vtmn-block swiper-media__image svelte-11itto" width="" height="" src="https://contents.mediadecathlon.com/p2887710/k$173f64268196ba5c52d2525ef83fdb2a/sq/erkek-sicak-tutan-ve-su-gecirmez-yelkenli-montu-lacivert-100.jpg?format=auto&amp;f=800x0" srcset="https://contents.mediadecathlon.com/p2887710/k$173f64268196ba5c52d2525ef83fdb2a/sq/erkek-sicak-tutan-ve-su-gecirmez-yelkenli-montu-lacivert-100.jpg?format=auto&amp;f=240x240 240w, https://contents.mediadecathlon.com/p2887710/k$173f64268196ba5c52d2525ef83fdb2a/sq/erkek-sicak-tutan-ve-su-gecirmez-yelkenli-montu-lacivert-100.jpg?format=auto&amp;f=480x480 480w, https://contents.mediadecathlon.com/p2887710/k$173f64268196ba5c52d2525ef83fdb2a/sq/erkek-sicak-tutan-ve-su-gecirmez-yelkenli-montu-lacivert-100.jpg?format=auto&amp;f=720x720 720w, https://contents.mediadecathlon.com/p2887710/k$173f64268196ba5c52d2525ef83fdb2a/sq/erkek-sicak-tutan-ve-su-gecirmez-yelkenli-montu-lacivert-100.jpg?format=auto&amp;f=323x323 323w, https://contents.mediadecathlon.com/p2887710/k$173f64268196ba5c52d2525ef83fdb2a/sq/erkek-sicak-tutan-ve-su-gecirmez-yelkenli-montu-lacivert-100.jpg?format=auto&amp;f=646x646 646w, https://contents.mediadecathlon.com/p2887710/k$173f64268196ba5c52d2525ef83fdb2a/sq/erkek-sicak-tutan-ve-su-gecirmez-yelkenli-montu-lacivert-100.jpg?format=auto&amp;f=969x969 969w" sizes="(min-width: 360px) 40vw, (min-width: 0px) 90vw, 100vw" loading="lazy" aria-hidden="false" alt="Erkek S&#x131;cak Tutan ve Su Ge&#xE7;irmez Yelkenli Montu - Lacivert - 100" data-broken="G&#xF6;rsel kullan&#x131;lam&#x131;yor"/>
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

    private function extractLink(?string $html): string
    {
        if (empty($html)) {
            return "";
        }

        // Extract the first href attribute from anchor tags
        // Pattern matches: <a class="dpb-product-model-link svelte-1bclr8g" href="/p/kadin-binici-yelegi-siyah-100/_/R-p-177631?mc=8404044&amp;c=S&#x130;YAH" tabindex="-1"><span class="vh">Kad&#305;n Binici Yele&#287;i - Siyah - 100</span></a>\n
        if (preg_match('/<a[^>]+href=["\']([^"\']+)["\']/i', $html, $matches)) {
            return explode('&', $matches[1])[0] ?? "";
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

        $relatedProducts = [];
        $endpoints = [];
        foreach ($productData['related_products'] as $relatedProduct) {
            if(strlen($relatedProduct) > 5 && $relatedProduct != $productData['code']) {
                $otherUrl = explode('?', $url)[0] . "?mc=" . $relatedProduct;
                $relatedProducts[] = $otherUrl;
                $endpoints[] = [
                    'url' => $otherUrl,
                    'brand_id' => $brandId,
                    'category_id' => $categoryId,
                    'status' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }



        $product = Product::updateOrCreate([
            'url' => $url,
        ], [
            'title' => $productData['title'],
            'code' => $productData['code'] ?? null,
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
            'related_products' => !empty($relatedProducts) ? json_encode($relatedProducts) : null,
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

            $priority = 100;
            // Create or update sizes
            foreach ($productData['size'] as $key => $status) {
                $sizeTitle = trim($key, '.');

                switch (strtolower($status)) {
                    case 'instock':
                        $stock = config('product.default_stock');
                        break;
                    case 'low':
                        $stock = 5;
                        break;
                    case 'outofstock':
                        $stock = 0;
                        break;
                    default:
                        $stock = config('product.default_stock');
                        break;
                }

                $product->sizes()->updateOrCreate(
                    ['code' => $sizeTitle],
                    [
                        'title' => $sizeTitle,
                        'status' => 1,
                        'stock' => $stock,
                        'priority' => $priority,
                    ]
                );
                $priority--;
            }
        }

        if (!empty($endpoints)) {
            // Extract URLs to check which ones already exist
            $urls = array_column($endpoints, 'url');

            // Get existing URLs from database
            $existingUrls = Endpoint::whereIn('url', $urls)
                ->pluck('url')
                ->toArray();

            // Filter out endpoints that already exist
            $newEndpoints = array_filter($endpoints, function ($endpoint) use ($existingUrls) {
                return !in_array($endpoint['url'], $existingUrls);
            });

            // Only insert new endpoints
            if (!empty($newEndpoints)) {
                Endpoint::insert(array_values($newEndpoints));
            }
        }

        return $product;
    }
}