<?php

namespace Domain\Product\DTO;

final class ProductRefreshOutcome
{
    public const STATUS_SUCCESS = 'success';

    public const STATUS_FAILED = 'failed';

    public const STATUS_SKIPPED = 'skipped';

    public function __construct(
        public readonly int $productId,
        public readonly ?string $code,
        public readonly ?int $brandId,
        public readonly string $status,
        public readonly ?string $reason = null,
        public readonly ?string $action = null,
    ) {}

    public function isSuccess(): bool
    {
        return $this->status === self::STATUS_SUCCESS;
    }

    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    /**
     * @return array{
     *     product_id: int,
     *     code: ?string,
     *     brand_id: ?int,
     *     status: string,
     *     reason: ?string,
     *     action: ?string
     * }
     */
    public function toArray(): array
    {
        return [
            'product_id' => $this->productId,
            'code' => $this->code,
            'brand_id' => $this->brandId,
            'status' => $this->status,
            'reason' => $this->reason,
            'action' => $this->action,
        ];
    }
}
