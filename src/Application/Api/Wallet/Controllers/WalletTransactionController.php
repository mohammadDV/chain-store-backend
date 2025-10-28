<?php

namespace Application\Api\Wallet\Controllers;

use Core\Http\Controllers\Controller;
use Core\Http\Requests\TableRequest;
use Domain\Wallet\Models\Wallet;
use Domain\Wallet\Repositories\Contracts\IWalletTransactionRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class WalletTransactionController extends Controller
{
    public function __construct(
        private readonly IWalletTransactionRepository $repository,
    ) {
        //
    }

    /**
     * Get all of wallet transaction with pagination
     * @param TableRequest $request
     * @param Wallet $wallet
     * @return JsonResponse
     */
    public function index(TableRequest $request, Wallet $wallet): JsonResponse
    {
        return response()->json($this->repository->index($request, $wallet), Response::HTTP_OK);
    }

}
