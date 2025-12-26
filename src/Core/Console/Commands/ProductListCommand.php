<?php

namespace Core\Console\Commands;

use Domain\Product\Services\OxylabsService;
use Illuminate\Console\Command;
use Core\Console\Commands\Traits\RequestTrait;
use Domain\Product\Models\Endpoint;
use Domain\Product\Services\Brands\BrandServiceFactory;
use Domain\Product\Services\Brands\BrandEnum;
use Domain\Product\Models\CategoryEndpoint;

class ProductListCommand extends Command
{
    use RequestTrait;
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

        $categoryEndpoints = CategoryEndpoint::query()
            ->with('brand', 'category')
            ->where('status', 0)
            ->get();

        foreach($categoryEndpoints as $categoryEndpoint) {

            $brandId = $categoryEndpoint->brand_id;
            $categoryId = $categoryEndpoint->category_id;

            $brand = $categoryEndpoint->brand;
            $category = $categoryEndpoint->category;

            if (!$brand) {
                $this->error("Brand with ID {$brandId} not found.");
                return 1;
            }

            if (!$category) {
                $this->error("Category with ID {$categoryId} not found.");
                return 1;
            }

            // Get brand service using factory
            try {
                $brandService = BrandServiceFactory::getService($brand);
            } catch (\Exception $e) {
                $this->error("Error getting brand service: " . $e->getMessage());
                return 1;
            }

            // Get pagination parameters from BrandEnum
            try {
                $paginationParam = BrandEnum::getProductListPaginationParam($brand->slug);
                $paginationSkip = BrandEnum::getProductListPaginationSkip($brand->slug);
            } catch (\ValueError $e) {
                $this->error("Error getting pagination parameters for brand slug '{$brand->slug}': " . $e->getMessage());
                return 1;
            }

            $parsingKey = $brandService->getProductListParsingKey();

            $offset = 0;
            $stop = false;

            // Get existing URLs from database
            $existingUrls = Endpoint::query()
                ->where('brand_id', $brand->id)
                ->where('category_id', $category->id)
                ->pluck('url')
                ->toArray();

            while(!$stop) {
                // Build URL with brand-specific pagination parameter
                $url = $categoryEndpoint->url . '?' . $paginationParam . '=' . $offset;

                $filters = $this->retryRequest($oxylabsService, $parsingKey, $url);

                if(!empty($filters['status']) && $filters['status'] == 2) {
                    $this->error("Connection error: " . $filters['error']);
                    $stop = true;
                    return;
                }

                // Use brand service to clean product list
                try {
                    $endpoints = $brandService->cleanProductList($filters, $brand, $category);
                } catch (\Exception $e) {
                    $this->error("Error cleaning product list: " . $e->getMessage());
                    $stop = true;
                    return;
                }

                $offset += $paginationSkip;
                $this->info("Endpoints: ".count($endpoints));

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

                // Stop if we got fewer results than the pagination skip value
                if(count($endpoints) < $paginationSkip) {
                    $stop = true;
                }

                sleep(5);
            }
            $categoryEndpoint->update([
                'status' => 1,
            ]);
            $this->info("Done: ".$offset);
        }

        $this->info("All done!");
    }

}
