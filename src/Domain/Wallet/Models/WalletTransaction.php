<?php

namespace Domain\Wallet\Models;

use Database\Factories\WalletTransactionFactory;
use Domain\User\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $wallet_id
 * @property string $type
 * @property numeric $amount
 * @property string $currency
 * @property string $status
 * @property string $reference
 * @property string|null $description
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User|null $user
 * @property-read Wallet $wallet
 */
class WalletTransaction extends Model
{
    /** @use HasFactory<WalletTransactionFactory> */
    use HasFactory;

    const PENDING = 'pending';

    const COMPLETED = 'completed';

    const FAILED = 'failed';

    const DEPOSITE = 'deposit';

    const WITHDRAWAL = 'withdrawal';

    const REFUND = 'refund';

    const TRANSFER = 'transfer';

    const PURCHASE = 'purchase';

    protected $guarded = [];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Wallet, $this>
     */
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    /**
     * Create a new transaction and update wallet balance.
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

        return (string) $reference;
    }

    protected static function newFactory(): WalletTransactionFactory
    {
        return WalletTransactionFactory::new();
    }
}
