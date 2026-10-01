<?php

namespace Domain\Payment\Repositories;

use Application\Api\Payment\Resources\TransactionsResource;
use Core\Http\Requests\TableRequest;
use Domain\Payment\Models\Transaction;
use Domain\Payment\Repositories\Contracts\IPaymentRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Morilog\Jalali\Jalalian;

class PaymentRepository implements IPaymentRepository
{
    /**
     * Get the identityRecords pagination.
     */
    public function index(TableRequest $request): LengthAwarePaginator
    {
        $search = $request->get('query');
        $type = $request->get('type');
        $status = $request->get('status');
        $transactions = Transaction::query()
            ->where('user_id', Auth::user()->id)
            ->when(! empty($search), function ($query) use ($search) {
                return $query->where(function ($nested) use ($search) {
                    $nested->where('bank_transaction_id', 'like', '%'.$search.'%')
                        ->orWhere('reference', 'like', '%'.$search.'%');
                });
            })
            ->when(! empty($type), function ($query) use ($type) {
                return $query->where('model_type', $type);
            })
            ->when(! empty($status), function ($query) use ($status) {
                return $query->where('status', $status);
            })
            ->orderBy($request->get('column', 'id'), $request->get('sort', 'desc'))
            ->paginate($request->get('count', 25));

        return $transactions->through(fn ($transaction) => new TransactionsResource($transaction));

    }

    /**
     * Get the identityRecord.
     */
    public function show(string $bankTransactionId): array
    {

        $transaction = Transaction::query()
            ->where('bank_transaction_id', $bankTransactionId)
            ->firstOrFail();

        return [
            'bank_transaction_id' => $transaction->bank_transaction_id,
            'reference' => $transaction->reference,
            'status' => $transaction->status,
            'amount' => $transaction->amount,
            'message' => $transaction->message,
            'date' => Jalalian::fromDateTime($transaction->created_at)->format('Y-m-d H:i:s'),
        ];
    }
}
