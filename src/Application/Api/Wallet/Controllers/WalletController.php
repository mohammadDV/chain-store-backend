<?php

namespace Application\Api\Wallet\Controllers;


use Application\Api\Wallet\Requests\TopUpRequest;
use Application\Api\Wallet\Requests\TransferRequest;
use Application\Api\Wallet\Requests\WithdrawRequest;
use Core\Http\Controllers\Controller;
use Core\Http\Requests\TableRequest;
use Domain\Wallet\Repositories\Contracts\IWalletRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class WalletController extends Controller
{
    public function __construct(
        private readonly IWalletRepository $repository,
    ) {
        //
    }

    /**
     * Get all of wallet with pagination
     * @param TableRequest $request
     * @return JsonResponse
     */
    public function index(TableRequest $request): JsonResponse
    {
        return response()->json($this->repository->index($request), Response::HTTP_OK);
    }

    /**
     * Display the specified resource.
     */
    public function show(): JsonResponse
    {
        $wallet = $this->repository->findByUserId(auth()->id());

        return response()->json([
            'status' => 1,
            'data' => [
                'balance' => $wallet->balance,
                'available_balance' => $this->repository->getAvailableBalance($wallet),
                'currency' => $wallet->currency,
            ],
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function topUp(TopUpRequest $request)
    {
        return $this->repository->topUp($request);
    }

    public function transfer(TransferRequest $request): JsonResponse
    {
        return $this->repository->transfer($request);
    }

    public function withdraw(WithdrawRequest $request): JsonResponse
    {
        return $this->repository->withdraw($request);
    }
}