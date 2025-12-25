<?php

namespace Core\Console\Commands;

use Domain\Product\Models\Product;
use Domain\Product\Models\Size;
use Domain\Product\Services\Brands\BrandServiceFactory;
use Domain\Product\Services\OxylabsService;
use Illuminate\Console\Command;
use Core\Console\Commands\Traits\RequestTrait;

class UpdateStockCommand extends Command
{
    use RequestTrait;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'get-data:update-stock {--limit=10 : Limit the number of sizes to process}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch product from Oxylabs and update stock in the database';

    /**
     * Execute the console command.
     */
    public function handle()
    {

        $oxylabsService = new OxylabsService();

        $sizes = Size::query()
            ->with('product.brand')
            ->whereHas('product', function($query) {
                $query->where('status', Product::COMPLETED)
                    ->where('brand_id', 1)
                    ->where('is_failed', 0)
                    ->where('active', 1);
            })
            ->orderBy('updated_at', 'asc')
            ->limit($this->option('limit', 10))
            ->get();


        $count = 0;
        foreach($sizes as $size) {

            try {
                $url = $size?->product?->url . '?forceSelSize=' . str_replace(' ', '+', $size->code);
                // Get the appropriate brand service
                $brandService = BrandServiceFactory::getService($size?->product?->brand);
                $parsingKey = $brandService->getUpdateStockParsingKey();

                $filters = $this->retryRequest($oxylabsService, $parsingKey, $url, 1);

                if(!empty($filters['status']) && in_array($filters['status'], [4, 2])) {
                    continue;
                }

                // Use brand service to clean product data
                $productData = $brandService->cleanStockData($filters, $size?->product?->brand?->domain, $size->code);

                if(!empty($productData['status']) && $productData['status'] == 2) {
                    continue;
                }

                if(!empty($productData['status']) && $productData['status'] == 3) {

                    $this->error("Failed to get product sizesssssssssss: " . $url);
                    continue;
                }

                $product = $brandService->updateProduct($productData, $size?->product, $size);

                if (!empty($product?->id)) {
                    $count++;
                    $this->info("Products: " . $product->id);
                    $this->info("Size: " . $size->id);
                    $this->info("Count: {$count}/{$sizes->count()}");
                }
            } catch (\Exception $e) {
                $this->error("Error processing size {$size->id}: " . $e->getMessage());
                continue;
            }
        }
    }
}