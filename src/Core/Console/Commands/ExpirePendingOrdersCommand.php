<?php

namespace Core\Console\Commands;

use Domain\Product\Repositories\Contracts\IOrderRepository;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ExpirePendingOrdersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:expire-pending';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Expire pending orders that have been created more than one hour ago';

    /**
     * Execute the console command.
     */
    public function handle(IOrderRepository $orderRepository): int
    {
        $startTime = microtime(true);

        $this->info('Starting to expire pending orders...');
        Log::info('ExpirePendingOrdersCommand: Started', [
            'timestamp' => now()->toDateTimeString(),
        ]);

        try {
            $expiredCount = $orderRepository->expirePendingOrders();

            $executionTime = round(microtime(true) - $startTime, 2);

            $this->info("Successfully expired {$expiredCount} pending order(s).");
            Log::info('ExpirePendingOrdersCommand: Completed successfully', [
                'expired_count' => $expiredCount,
                'execution_time_seconds' => $executionTime,
                'timestamp' => now()->toDateTimeString(),
            ]);

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $executionTime = round(microtime(true) - $startTime, 2);

            $this->error("Failed to expire pending orders: {$e->getMessage()}");
            Log::error('ExpirePendingOrdersCommand: Failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'execution_time_seconds' => $executionTime,
                'timestamp' => now()->toDateTimeString(),
            ]);

            return Command::FAILURE;
        }
    }
}
