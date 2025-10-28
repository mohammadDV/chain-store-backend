<?php

namespace Domain\Wallet\Models;

use Domain\User\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WalletTransaction extends Model
{

    const PENDING = "pending";
    const COMPLETED = "completed";
    const FAILED = "failed";
    const DEPOSITE = "deposit";
    const WITHDRAWAL = "withdrawal";
    const REFUND = "refund";
    const TRANSFER = "transfer";
    const PURCHASE = "purchase";

    protected $guarded = [];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    /**
     * Create a new transaction and update wallet balance.
     *
     * @param Wallet $wallet
     * @param float $amount
     * @param string $type
     * @param string $description
     * @param string $status
     * @return WalletTransaction
     */
    public static function createTransaction(
        Wallet $wallet,
        float $amount,
        string $type,
        string $description,
        string $status = self::COMPLETED
    ): WalletTransaction {
        // Create transaction record
        $transaction = self::create([
            'wallet_id' => $wallet->id,
            'type' => $type,
            'amount' => $amount,
            'currency' => $wallet->currency,
            'status' => $status,
            'reference' => self::generateReference(),
            'description' => $description,
        ]);

        // Update wallet balance. This works for both positive (credit) and negative (debit) amounts.
        $wallet->increment('balance', $amount);

        return $transaction;
    }

    public static function generateReference(): string
    {
        do {
            $reference = random_int(1111111111, 9999999999);
            $exists = self::query()
                ->where('reference', $reference)
                ->exists();
        } while ($exists);

        return (string)$reference;
    }
}