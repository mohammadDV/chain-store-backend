<?php

namespace Domain\Product\Jobs;

use Domain\Product\Repositories\Contracts\IOrderRepository;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ExpirePendingOrdersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var int
     */
    public $backoff = 60;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(IOrderRepository $orderRepository): void
    {
        $startTime = microtime(true);
        
        Log::info('ExpirePendingOrdersJob: Started', [
            'timestamp' => now()->toDateTimeString(),
        ]);

        try {
            $expiredCount = $orderRepository->expirePendingOrders();
            
            $executionTime = round(microtime(true) - $startTime, 2);
            
            Log::info('ExpirePendingOrdersJob: Completed successfully', [
                'expired_count' => $expiredCount,
                'execution_time_seconds' => $executionTime,
                'timestamp' => now()->toDateTimeString(),
            ]);
        } catch (\Exception $e) {
            $executionTime = round(microtime(true) - $startTime, 2);
            
            Log::error('ExpirePendingOrdersJob: Failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'execution_time_seconds' => $executionTime,
                'timestamp' => now()->toDateTimeString(),
            ]);

            throw $e; // Re-throw to mark job as failed
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('ExpirePendingOrdersJob: Permanently failed', [
            'error' => $exception->getMessage(),
            'timestamp' => now()->toDateTimeString(),
        ]);
    }
}

