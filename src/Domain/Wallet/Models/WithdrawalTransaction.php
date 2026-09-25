<?php

namespace Domain\Wallet\Models;

use Database\Factories\WithdrawalTransactionFactory;
use Domain\User\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $wallet_id
 * @property numeric $amount
 * @property string $currency
 * @property string $status
 * @property string $reference
 * @property string|null $card
 * @property string|null $sheba
 * @property string|null $description
 * @property string|null $image
 * @property string|null $reason
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User|null $user
 * @property-read Wallet $wallet
 */
class WithdrawalTransaction extends Model
{
    /** @use HasFactory<WithdrawalTransactionFactory> */
    use HasFactory;

    const PENDING = 'pending';

    const COMPLETED = 'completed';

    const REJECT = 'reject';

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

    protected static function newFactory(): WithdrawalTransactionFactory
    {
        return WithdrawalTransactionFactory::new();
    }
}
