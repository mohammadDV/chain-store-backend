<?php

namespace Core\Console\Commands;

use Core\Console\Commands\Traits\RequestTrait;
use Domain\Product\Models\Product;
use Domain\Product\Services\OxylabsService;
use Domain\Product\Services\Brands\BrandServiceFactory;
use Illuminate\Console\Command;

class UpdateSizeAndStockCommand extends Command
{
    use RequestTrait;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update-data:decathlon-update-size-and-stock {--limit=10 : Limit the number of endpoints to process} {--failed=0 : Failed products} {--all=1 : Failed products} {--discount=0 : Discount products}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update size and stock of products for Decathlon';


    /**
     * Execute the console command.
     */
    public function handle()
    {

        $oxylabsService = new OxylabsService();

        $limit = (int) $this->option('limit');
        $isFailed = (int) $this->option('failed');
        $isAll = (int) $this->option('all');
        $isDiscount = (int) $this->option('discount');

        $products = Product::query()
            ->with('sizes')
            ->with('brand')
            ->where('brand_id', 2)
            ->where('status', Product::COMPLETED)
            ->whereNotNull('url')
            ->where(function($query) use ($isFailed) {
                $query->where('is_failed', $isFailed);
            })
            ->when($isAll == 0, function($query) {
                $query->whereDoesntHave('sizes');
            })
            ->when($isDiscount == 1, function($query) {
                $query->where('discount', '>', 0);
            })
            ->orderBy('updated_at', 'asc')
            ->limit($limit)
            ->get();

        $count = 0;
        foreach($products as $product) {
            // Skip if brand is missing
            if (!$product?->brand) {
                $this->error("Endpoint {$product->id} has no brand assigned");
                continue;
            }

            try {
                // Get the appropriate brand service
                $brandService = BrandServiceFactory::getService($product->brand);
                $parsingKey = $brandService->getUpdateStockParsingKey();

                $filters = $this->retryRequest($oxylabsService, $parsingKey, $product->url);

                if(!empty($filters['status']) && in_array($filters['status'], [4, 2])) {
                    continue;
                }

                // Use brand service to clean product data
                $productData = $brandService->cleanProductData($filters, $product?->brand?->domain);

                if(!empty($productData['status']) && $productData['status'] == 2) {
                    continue;
                }

                if(!empty($productData['status']) && $productData['status'] == 3) {

                    $product->update([
                        'is_failed' => 1,
                        'updated_at' => now(),
                    ]);
                    $this->error("Failed to get product sizes: " . $product->id . " - " . $product->url);
                    continue;
                }

                if(empty($productData['price'])) {
                    $this->error("Price not found: " . $product->id . " - " . $product->url);
                    continue;
                }

                $finalProduct = $brandService->updateProduct($productData, $product);

                if (!empty($finalProduct?->id)) {
                    $count++;
                    $this->info("Products: " . $product->url);
                    $this->info("Products: " . $finalProduct->id);
                    $this->info("count: " . $count . " / " . $endpoints->count());
                    $this->info("************************************************");
                }
            } catch (\Exception $e) {
                $this->error("Error processing endpoint {$product->id}: " . $e->getMessage());
                continue;
            }
            sleep(2);
        }

        $this->info("Done: " . $count);
    }
}