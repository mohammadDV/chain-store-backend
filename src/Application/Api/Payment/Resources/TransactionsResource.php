<?php

namespace Application\Api\Payment\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Morilog\Jalali\Jalalian;

class TransactionsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'bank_transaction_id' => $this->bank_transaction_id,
            'reference' => $this->reference,
            'status' => $this->status,
            'amount' => $this->amount,
            'message' => $this->message,
            'type' => $this->model_type,
            'created_at' => $this->created_at ? Jalalian::fromDateTime($this->created_at)->format('Y-m-d H:i:s') : null,
        ];
    }
}
