<?php

namespace Domain\Product\Services\Brands;

use Domain\Brand\Models\Brand;
use Domain\Product\Models\Category;
use Domain\Product\Models\Product;

/**
 * Interface for brand-specific product scraping services
 *
 * Each brand (Adidas, Decathlon, Poma, etc.) should implement this interface
 * to provide brand-specific extraction logic for products and product lists.
 */
interface BrandServiceInterface
{
    /**
     * Clean and normalize product data from API response
     *
     * @param array $response The raw response from Oxylabs API
     * @param string $domain The brand domain (e.g., 'https://www.adidas.com.tr')
     * @return array Normalized product data with keys: title, images, size, price, discount, related_products
     * @throws \Exception If required product data is missing
     */
    public function cleanProductData(array $response, string $domain): array;

    /**
     * Clean and normalize product list data from API response
     *
     * @param array $response The raw response from Oxylabs API
     * @param Brand $brand The brand model
     * @param Category $category The category model
     * @return array Array of endpoint data with keys: url, brand_id, category_id, status, created_at, updated_at
     * @throws \Exception If required data is missing
     */
    public function cleanProductList(array $response, Brand $brand, Category $category): array;

    /**
     * Get the Oxylabs parsing key for product scraping
     * This determines which parsing configuration to use in OxylabsService
     *
     * @return string The key (e.g., 'product', 'productList')
     */
    public function getProductParsingKey(): string;

    /**
     * Get the Oxylabs parsing key for product list scraping
     *
     * @return string The key (e.g., 'productList')
     */
    public function getProductListParsingKey(): string;

    /**
     * Store product data in the database
     *
     * @param array $productData Product data to store
     * @param int $categoryId Category ID
     * @param string $url Product URL
     * @param int $brandId Brand ID
     * @return Product Product model
     */
    public function storeProduct(array $productData, int $categoryId, string $url, int $brandId): Product;
}
