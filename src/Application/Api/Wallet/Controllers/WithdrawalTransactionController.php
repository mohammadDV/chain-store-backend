<?php

namespace Application\Api\Wallet\Controllers;


use Application\Api\Wallet\Requests\WithdrawRequest;
use Application\Api\Wallet\Requests\WithdrawalStatusRequest;
use Core\Http\Controllers\Controller;
use Core\Http\Requests\TableRequest;
use Domain\Wallet\Repositories\Contracts\IWithdrawalTransactionRepository;
use Domain\Wallet\Models\WithdrawalTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class WithdrawalTransactionController extends Controller
{
    public function __construct(
        private readonly IWithdrawalTransactionRepository $repository,
    ) {
        //
    }

    /**
     * Get all of WithdrawalTransaction with pagination
     * @param TableRequest $request
     * @return JsonResponse
     */
    public function index(TableRequest $request): JsonResponse
    {
        return response()->json($this->repository->index($request), Response::HTTP_OK);
    }

    public function store(WithdrawRequest $request): JsonResponse
    {
        return $this->repository->store($request);
    }

    public function updateStatus(WithdrawalTransaction $withdrawalTransaction, WithdrawalStatusRequest $request): JsonResponse
    {
        return $this->repository->updateStatus($withdrawalTransaction, $request);
    }
}
