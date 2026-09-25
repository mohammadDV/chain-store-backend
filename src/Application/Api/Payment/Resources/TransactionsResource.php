<?php

namespace Application\Api\Payment\Resources;

use Domain\Payment\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Morilog\Jalali\Jalalian;

/**
 * @property-read Transaction $resource
 */
class TransactionsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'bank_transaction_id' => $this->resource->bank_transaction_id,
            'reference' => $this->resource->reference,
            'status' => $this->resource->status,
            'amount' => $this->resource->amount,
            'message' => $this->resource->message,
            'type' => $this->resource->model_type,
            'created_at' => $this->resource->created_at ? Jalalian::fromDateTime($this->resource->created_at)->format('Y-m-d H:i:s') : null,
        ];
    }
}
