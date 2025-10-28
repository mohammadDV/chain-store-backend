<?php

namespace Application\Api\Payment\Controllers;

use App\Domain\Transaction\Repositories\ITransactionRepository;
use App\Domain\Wallet\Repositories\IWalletRepository;
use App\Domain\Transaction\Models\Transaction;
use Core\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function __construct(
        private readonly ITransactionRepository $transactionRepository,
        private readonly IWalletRepository $walletRepository
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $wallet = $this->walletRepository->findByUserId(auth()->id());

        $transactions = $this->transactionRepository->findByWalletId($wallet->id, [
            'type' => $request->type,
            'status' => $request->status,
            'date_from' => $request->date_from,
            'date_to' => $request->date_to,
            'per_page' => $request->per_page,
        ]);

        return response()->json([
            'status' => 1,
            'data' => $transactions,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Transaction $transaction): JsonResponse
    {
        // Ensure the transaction belongs to the authenticated user
        $wallet = $this->walletRepository->findByUserId(auth()->id());

        if ($transaction->wallet_id !== $wallet->id) {
            return response()->json([
                'status' => 0,
                'message' => 'Transaction not found',
            ], 404);
        }

        $transaction->load(['bankTransaction', 'recipientWallet.user']);

        return response()->json([
            'status' => 1,
            'data' => $transaction,
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
}
