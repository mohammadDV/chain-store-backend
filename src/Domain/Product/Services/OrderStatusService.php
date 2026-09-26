<?php

namespace Domain\Product\Services;

use Domain\Notification\Services\NotificationService;
use Domain\Product\Enums\OrderLedgerSource;
use Domain\Product\Enums\OrderLedgerType;
use Domain\Product\Exceptions\OrderAlreadyRefundedException;
use Domain\Product\Models\Order;
use Domain\Product\Models\OrderLedger;
use Domain\Product\Models\OrderProduct;
use Domain\User\Models\User;
use Domain\Wallet\Models\Wallet;
use Domain\Wallet\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class OrderStatusService
{
    public function __construct(
        protected OrderLedgerService $ledgerService,
    ) {}

    /**
     * @param  array<string, mixed>|null  $meta
     */
    public function transition(
        Order $order,
        string $toStatus,
        string $source = OrderLedgerSource::System,
        ?User $actor = null,
        ?string $message = null,
        ?array $meta = null,
    ): Order {
        return DB::transaction(function () use ($order, $toStatus, $source, $actor, $message, $meta) {
            /** @var Order $locked */
            $locked = Order::query()
                ->whereKey($order->id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->assertNotRefunded($locked);
            $this->assertNoLineAlreadyRefunded($locked);

            $fromStatus = $locked->status;

            if ($fromStatus === $toStatus) {
                return $locked;
            }

            if ($toStatus === Order::REFUNDED) {
                $this->assertOrderRefundable($fromStatus);
            }

            // Atomic claim: only one concurrent request can win the status change.
            $claimed = Order::query()
                ->whereKey($locked->id)
                ->where('status', $fromStatus)
                ->update(['status' => $toStatus]);

            if ($claimed === 0) {
                $locked->refresh();
                $this->assertNotRefunded($locked);

                if ($locked->status === $toStatus) {
                    return $locked;
                }

                throw new OrderAlreadyRefundedException(__('site.order_status_change_conflict'));
            }

            $locked->setAttribute('status', $toStatus);

            $type = $this->resolveOrderType($toStatus);

            if ($toStatus === Order::REFUNDED) {
                $this->refundEntireOrder($locked);
            }

            $this->ledgerService->record(
                order: $locked,
                type: $type,
                source: $source,
                fromStatus: $fromStatus,
                toStatus: $toStatus,
                userId: $actor?->id,
                message: $message,
                meta: $meta,
            );

            return $locked->refresh();
        });
    }

    /**
     * Mark order as paid. Returns null when the order cannot be paid (already paid or invalid status).
     *
     * @param  array<string, mixed>|null  $meta
     * @param  list<string>|null  $allowedFromStatuses  Defaults to pending only; bank callbacks may also allow expired.
     */
    public function markPaid(
        Order $order,
        string $source = OrderLedgerSource::Customer,
        ?int $userId = null,
        ?string $message = null,
        ?array $meta = null,
        bool $useExistingTransaction = false,
        ?array $allowedFromStatuses = null,
    ): ?Order {
        $allowedFromStatuses ??= [Order::PENDING];

        $callback = function () use ($order, $source, $userId, $message, $meta, $allowedFromStatuses) {
            /** @var Order $locked */
            $locked = Order::query()
                ->whereKey($order->id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->assertNotRefunded($locked);

            if ($locked->status === Order::PAID) {
                return null;
            }

            if (! in_array($locked->status, $allowedFromStatuses, true)) {
                return null;
            }

            $fromStatus = $locked->status;
            $locked->update(['status' => Order::PAID]);

            $this->ledgerService->record(
                order: $locked,
                type: OrderLedgerType::Paid,
                source: $source,
                fromStatus: $fromStatus,
                toStatus: Order::PAID,
                userId: $userId,
                message: $message,
                meta: $meta,
            );

            return $locked->refresh();
        };

        if ($useExistingTransaction) {
            return $callback();
        }

        return DB::transaction($callback);
    }

    /**
     * Record order creation ledger. Call inside an open transaction after the order row exists.
     */
    public function recordCreated(
        Order $order,
        string $source = OrderLedgerSource::Customer,
        ?int $userId = null,
        ?string $message = null,
    ): OrderLedger {
        return $this->ledgerService->record(
            order: $order,
            type: OrderLedgerType::Created,
            source: $source,
            fromStatus: null,
            toStatus: Order::PENDING,
            userId: $userId,
            message: $message,
        );
    }

    /**
     * Expire a single pending order. Returns true when expired.
     */
    public function expire(Order $order): bool
    {
        return DB::transaction(function () use ($order) {
            /** @var Order|null $locked */
            $locked = Order::query()
                ->whereKey($order->id)
                ->lockForUpdate()
                ->first();

            if (! $locked || $locked->status !== Order::PENDING) {
                return false;
            }

            $fromStatus = $locked->status;
            $locked->update(['status' => Order::EXPIRED]);

            $this->ledgerService->record(
                order: $locked,
                type: OrderLedgerType::Expired,
                source: OrderLedgerSource::System,
                fromStatus: $fromStatus,
                toStatus: Order::EXPIRED,
            );

            return true;
        });
    }

    /**
     * @param  array<string, mixed>|null  $meta
     */
    public function transitionLine(
        Order $order,
        int $orderProductId,
        string $toStatus,
        string $source = OrderLedgerSource::Admin,
        ?User $actor = null,
        ?string $message = null,
        ?array $meta = null,
    ): Order {
        return DB::transaction(function () use ($order, $orderProductId, $toStatus, $source, $actor, $message, $meta) {
            /** @var Order $locked */
            $locked = Order::query()
                ->whereKey($order->id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->assertNotRefunded($locked);

            /** @var OrderProduct $line */
            $line = OrderProduct::query()
                ->whereKey($orderProductId)
                ->where('order_id', $locked->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($line->status === Order::REFUNDED) {
                throw new OrderAlreadyRefundedException(__('site.product_already_refunded'));
            }

            $fromStatus = $line->status;

            if ($fromStatus === $toStatus) {
                return $locked;
            }

            if ($toStatus === Order::REFUNDED) {
                $this->assertOrderRefundable($locked->status);
            }

            $claimed = OrderProduct::query()
                ->whereKey($line->id)
                ->where('status', $fromStatus)
                ->update(['status' => $toStatus]);

            if ($claimed === 0) {
                $line->refresh();

                if ($line->status === Order::REFUNDED) {
                    throw new OrderAlreadyRefundedException(__('site.product_already_refunded'));
                }

                if ($line->status === $toStatus) {
                    return $locked;
                }

                throw new OrderAlreadyRefundedException(__('site.order_status_change_conflict'));
            }

            $line->setAttribute('status', $toStatus);

            if ($toStatus === Order::REFUNDED) {
                // Line already claimed as refunded above; any remaining rows are other lines.
                $otherLinesRemain = OrderProduct::query()
                    ->where('order_id', $locked->id)
                    ->where('status', '!=', Order::REFUNDED)
                    ->exists();

                $refundableLeft = $this->remainingRefundable($locked);
                $lineGross = $this->lineRefundAmount($line);

                // Cap at total_amount so discounts cannot over-credit.
                // On the last line, credit everything still owed (delivery remainder included) in one wallet hit.
                $creditAmount = $otherLinesRemain
                    ? min($lineGross, $refundableLeft)
                    : $refundableLeft;

                $this->creditWallet($locked, $creditAmount, __('site.order_product_refunded'));

                $this->ledgerService->record(
                    order: $locked,
                    type: OrderLedgerType::LineRefunded,
                    source: $source,
                    fromStatus: $fromStatus,
                    toStatus: $toStatus,
                    userId: $actor?->id,
                    message: $message,
                    meta: array_merge($meta ?? [], [
                        'product_id' => $line->product_id,
                        'amount' => $creditAmount,
                        'unit_amount' => (float) $line->amount,
                        'count' => (int) $line->count,
                        'line_gross' => $lineGross,
                    ]),
                    orderProductId: $line->id,
                );

                if (! $otherLinesRemain) {
                    $orderFrom = $locked->status;
                    $locked->update(['status' => Order::REFUNDED]);

                    $this->ledgerService->record(
                        order: $locked,
                        type: OrderLedgerType::Refunded,
                        source: $source,
                        fromStatus: $orderFrom,
                        toStatus: Order::REFUNDED,
                        userId: $actor?->id,
                        message: $message,
                        meta: array_merge($meta ?? [], [
                            'from_line_refund' => true,
                            'credited_amount' => $creditAmount,
                        ]),
                    );
                }
            } else {
                $this->ledgerService->record(
                    order: $locked,
                    type: OrderLedgerType::StatusChanged,
                    source: $source,
                    fromStatus: $fromStatus,
                    toStatus: $toStatus,
                    userId: $actor?->id,
                    message: $message,
                    meta: array_merge($meta ?? [], ['product_id' => $line->product_id]),
                    orderProductId: $line->id,
                );
            }

            return $locked->refresh();
        });
    }

    private function assertNotRefunded(Order $order): void
    {
        if ($order->status === Order::REFUNDED) {
            throw new OrderAlreadyRefundedException(__('site.order_already_refunded'));
        }
    }

    private function assertOrderRefundable(string $status): void
    {
        $refundable = [
            Order::PAID,
            Order::SHIPPED,
            Order::DELIVERED,
            Order::RETURNED,
        ];

        if (! in_array($status, $refundable, true)) {
            throw new InvalidArgumentException(__('site.order_not_refundable'));
        }
    }

    private function assertNoLineAlreadyRefunded(Order $order): void
    {
        $exists = OrderProduct::query()
            ->where('order_id', $order->id)
            ->where('status', Order::REFUNDED)
            ->exists();

        if ($exists) {
            throw new OrderAlreadyRefundedException(__('site.one_of_the_products_already_refunded'));
        }
    }

    private function resolveOrderType(string $toStatus): OrderLedgerType
    {
        return match ($toStatus) {
            Order::PAID => OrderLedgerType::Paid,
            Order::EXPIRED => OrderLedgerType::Expired,
            Order::REFUNDED => OrderLedgerType::Refunded,
            default => OrderLedgerType::StatusChanged,
        };
    }

    private function refundEntireOrder(Order $order): void
    {
        OrderProduct::query()
            ->where('order_id', $order->id)
            ->update(['status' => Order::REFUNDED]);

        $this->creditWallet(
            $order,
            (float) $order->total_amount,
            __('site.order_product_refunded'),
        );
    }

    private function lineRefundAmount(OrderProduct $line): float
    {
        return (float) $line->amount * max(1, (int) $line->count);
    }

    /**
     * How much of total_amount has not yet been credited via line refunds.
     * Caps cumulative refunds so discounts cannot over-credit the wallet.
     */
    private function remainingRefundable(Order $order): float
    {
        $credited = OrderLedger::query()
            ->where('order_id', $order->id)
            ->where('type', OrderLedgerType::LineRefunded)
            ->get()
            ->sum(fn (OrderLedger $row) => (float) ($row->meta['amount'] ?? 0));

        return max(0, round((float) $order->total_amount - $credited, 2));
    }

    private function creditWallet(Order $order, float $amount, string $description): void
    {
        if ($amount <= 0) {
            return;
        }

        $wallet = Wallet::query()
            ->where('user_id', $order->user_id)
            ->where('currency', Wallet::IRR)
            ->lockForUpdate()
            ->first();

        if (! $wallet) {
            throw new InvalidArgumentException('Wallet not found for order user.');
        }

        WalletTransaction::createTransaction(
            wallet: $wallet,
            amount: $amount,
            type: WalletTransaction::DEPOSITE,
            description: $description,
            status: WalletTransaction::COMPLETED,
        );

        $order->loadMissing('user');

        if (! $order->user) {
            throw new InvalidArgumentException('Order user not found for refund notification.');
        }

        NotificationService::create([
            'title' => __('site.order_product_refunded_title'),
            'content' => __('site.order_product_refunded_content', [
                'amount' => number_format($amount, 2),
                'currency' => __('site.currency'),
            ]),
            'id' => $order->id,
            'type' => NotificationService::ORDER,
        ], $order->user);
    }
}
