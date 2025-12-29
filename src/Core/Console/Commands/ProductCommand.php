<?php

namespace Core\Console\Commands;

use Core\Console\Commands\Traits\RequestTrait;
use Domain\Product\Models\Endpoint;
use Domain\Product\Services\OxylabsService;
use Domain\Product\Services\Brands\BrandServiceFactory;
use Illuminate\Console\Command;

class ProductCommand extends Command
{
    use RequestTrait;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'get-data:product {--limit=10 : Limit the number of endpoints to process}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch product from Oxylabs';


    /**
     * Execute the console command.
     */
    public function handle()
    {

        $oxylabsService = new OxylabsService();

        $limit = (int) $this->option('limit');

        $endpoints = Endpoint::query()
            // ->where('brand_id', 2)
            ->with('brand')
            ->where('status', 0)
            ->WhereNotNull('url')
            ->orderBy('id', 'asc')
            ->limit($limit)
            ->get();

        // $this->url = 'https://www.adidas.com.tr/tr/almanya-25-kadin-takimi-deplasman-formasi/JF2605.html';

        $count = 0;
        foreach($endpoints as $endpoint) {
            // Skip if brand is missing
            if (!$endpoint->brand) {
                $this->error("Endpoint {$endpoint->id} has no brand assigned");
                continue;
            }

            try {
                // Get the appropriate brand service
                $brandService = BrandServiceFactory::getService($endpoint->brand);
                $parsingKey = $brandService->getProductParsingKey();

                $filters = $this->retryRequest($oxylabsService, $parsingKey, $endpoint->url, 1);

                if(!empty($filters['status']) && $filters['status'] == 4) {
                    continue;
                }

                if(!empty($filters['status']) && $filters['status'] == 2) {
                    continue;
                }

                // Use brand service to clean product data
                $productData = $brandService->cleanProductData($filters, $endpoint->brand->domain);

                if(!empty($productData['status']) && $productData['status'] == 2) {
                    continue;
                }

                if(!empty($productData['status']) && $productData['status'] == 3) {

                    $this->error("Failed to get product sizesssssssssss: " . $endpoint->url);
                    continue;
                }


                $product = $brandService->storeProduct($productData, $endpoint->category_id, $endpoint->url, $endpoint->brand->id);

                if (!empty($product?->id)) {
                    $endpoint->update([
                        'status' => 1,
                    ]);
                    $count++;
                    $this->info("Products: " . $endpoint->url);
                    $this->info("Products: " . $product->id);
                    $this->info("count: " . $count . " / " . $endpoints->count());
                    $this->info("************************************************");
                }
            } catch (\Exception $e) {
                $this->error("Error processing endpoint {$endpoint->id}: " . $e->getMessage());
                continue;
            }
            sleep(2);
        }

        $this->info("Done: " . $count);
    }
}
