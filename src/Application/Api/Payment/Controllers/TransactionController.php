<?php

namespace Application\Api\Payment\Controllers;

use Core\Http\Controllers\Controller;
use Domain\Payment\Models\Transaction;
use Domain\Wallet\Repositories\Contracts\IWalletRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function __construct(
        private readonly IWalletRepository $walletRepository
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $wallet = $this->walletRepository->findByUserId(auth()->id());

        $transactions = Transaction::query()
            ->where('user_id', $wallet->user_id)
            ->when($request->filled('type'), fn ($query) => $query->where('model_type', $request->type))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))
            ->when($request->filled('date_from'), fn ($query) => $query->whereDate('created_at', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn ($query) => $query->whereDate('created_at', '<=', $request->date_to))
            ->orderByDesc('id')
            ->paginate((int) $request->get('per_page', 25));

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
        $wallet = $this->walletRepository->findByUserId(auth()->id());

        if ($transaction->user_id !== $wallet->user_id) {
            return response()->json([
                'status' => 0,
                'message' => 'Transaction not found',
            ], 404);
        }

        return response()->json([
            'status' => 1,
            'data' => $transaction->load('user'),
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
