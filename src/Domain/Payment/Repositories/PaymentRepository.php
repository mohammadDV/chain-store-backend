<?php

namespace Domain\Payment\Repositories;

use Application\Api\Payment\Requests\ManualPaymentRequest;
use Core\Http\Requests\TableRequest;
use Domain\Payment\Models\Transaction;
use Domain\Payment\Repositories\Contracts\IPaymentRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Application\Api\Payment\Resources\TransactionsResource;
use Domain\IdentityRecord\Models\IdentityRecord;
use Domain\Plan\Models\Plan;
use Domain\User\Services\TelegramNotificationService;
use Illuminate\Http\Response;
use Morilog\Jalali\Jalalian;

class PaymentRepository implements IPaymentRepository
{

    /**
     * @param TelegramNotificationService $service
     */
    public function __construct(protected TelegramNotificationService $service)
    {

    }

    /**
     * Get the identityRecords pagination.
     * @param TableRequest $request
     * @return LengthAwarePaginator
     */
    public function index(TableRequest $request) :LengthAwarePaginator
    {
        $search = $request->get('query');
        $type = $request->get('type');
        $status = $request->get('status');
        $transactions = Transaction::query()
            ->where('user_id', Auth::user()->id)
            ->when(!empty($search), function ($query) use ($search) {
                return $query->where('bank_transaction_id', 'like', '%' . $search . '%')
                    ->orWhere('reference', 'like', '%' . $search . '%');
            })
            ->when(!empty($type), function ($query) use ($type) {
                return $query->where('model_type', $type);
            })
            ->when(!empty($status), function ($query) use ($status) {
                return $query->where('status', $status);
            })
            ->orderBy($request->get('column', 'id'), $request->get('sort', 'desc'))
            ->paginate($request->get('count', 25));


        return $transactions->through(fn ($transaction) => new TransactionsResource($transaction));

    }

    /**
     * Get the identityRecord.
     * @param IdentityRecord $identityRecord
     * @return array
     */
    public function show(string $bankTransactionId) : array
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

    /**
     * Manual payment.
     * @param ManualPaymentRequest $request
     * @return array
     */
    public function manualPayment(ManualPaymentRequest $request) : array
    {


        if (empty(Auth::user()->status)) {
            return [
                'status' => 0,
                'message' => __('site.Your account is not active yet. Please send a message to the admin from ticket section.'),
            ];
        }

        $amount = intval($request->input('amount'));

        if ($request->input('type') == Transaction::IDENTITY) {
            $identityRecord = IdentityRecord::where('user_id', Auth::user()->id)->first();

            if (!$identityRecord) {
                return [
                    'status' => 0,
                    'message' => __('site.identity_record_not_found'),
                ];
            }

            if ($identityRecord->status == IdentityRecord::INPROGRESS) {
                return [
                    'status' => 0,
                    'message' => __('site.identity_record_is_already_in_progress'),
                ];
            }

            if ($identityRecord->status != IdentityRecord::PENDING) {
                return [
                    'status' => 0,
                    'message' => __('site.identity_record_is_already_paid'),
                ];
            }

            $plan = Plan::find(config('plan.default_plan_id'));
            $amount = intval($plan->amount);

        } else {

            if (empty(Auth::user()->verified_at)) {
                return [
                    'status' => 0,
                    'message' => __('site.You must verify your account to top up your wallet'),
                ];
            }
        }

        $transaction = Transaction::create([
            'status' => Transaction::PENDING,
            'model_type' => $request->input('type'),
            'amount' => $amount,
            'image' => $request->input('image'),
            'user_id' => Auth::user()->id,
            'manual' => 1,
            'model_id' => !empty($identityRecord->id) ? $identityRecord->id : null,
        ]);


        if ($transaction) {
            // update identity record status
            if ($request->input('type') == Transaction::IDENTITY) {
                $identityRecord->status = IdentityRecord::INPROGRESS;
                $identityRecord->save();
            }


            $this->service->sendNotification(
                config('telegram.chat_id'),
                'پرداخت دستی جدید' . PHP_EOL .
                'user_id ' . Auth::user()->id . PHP_EOL .
                'nickname ' . Auth::user()->nickname . PHP_EOL .
                'amount ' . $amount . PHP_EOL .
                'type ' . $request->type
            );


            return [
                'status' => 1,
                'message' => __('site.transaction_created'),
            ];
        }

        return [
            'status' => 0,
            'message' => __('site.transaction_failed'),
        ];
    }

}
