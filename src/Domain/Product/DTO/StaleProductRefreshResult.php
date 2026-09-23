<?php

namespace Domain\Product\DTO;

final class StaleProductRefreshResult
{
    /**
     * @param  list<ProductRefreshOutcome>  $outcomes
     */
    public function __construct(
        public readonly int $selected,
        public readonly int $succeeded,
        public readonly int $failed,
        public readonly int $skipped,
        public readonly array $outcomes,
        public readonly string $message,
    ) {}

    public static function empty(string $message): self
    {
        return new self(
            selected: 0,
            succeeded: 0,
            failed: 0,
            skipped: 0,
            outcomes: [],
            message: $message,
        );
    }

    public function isEmpty(): bool
    {
        return $this->selected === 0;
    }

    /**
     * @return array{
     *     message: string,
     *     selected: int,
     *     succeeded: int,
     *     failed: int,
     *     skipped: int,
     *     outcomes: list<array>
     * }
     */
    public function toArray(): array
    {
        return [
            'message' => $this->message,
            'selected' => $this->selected,
            'succeeded' => $this->succeeded,
            'failed' => $this->failed,
            'skipped' => $this->skipped,
            'outcomes' => array_map(
                static fn (ProductRefreshOutcome $outcome) => $outcome->toArray(),
                $this->outcomes,
            ),
        ];
    }

    /**
     * Compact context for Horizon / application logs.
     *
     * @return array<string, mixed>
     */
    public function toLogContext(): array
    {
        return [
            'message' => $this->message,
            'selected' => $this->selected,
            'succeeded' => $this->succeeded,
            'failed' => $this->failed,
            'skipped' => $this->skipped,
            'failures' => array_values(array_map(
                static fn (ProductRefreshOutcome $outcome) => $outcome->toArray(),
                array_filter(
                    $this->outcomes,
                    static fn (ProductRefreshOutcome $outcome) => $outcome->isFailed() || $outcome->status === ProductRefreshOutcome::STATUS_SKIPPED,
                ),
            )),
        ];
    }
}
