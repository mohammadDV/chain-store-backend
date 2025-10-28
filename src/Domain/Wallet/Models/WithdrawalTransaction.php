<?php

namespace Domain\Wallet\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Domain\User\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WithdrawalTransaction extends Model
{
    /** @use HasFactory<\Database\Factories\WithdrawalTransactionFactory> */
    use HasFactory;

    const PENDING = "pending";
    const COMPLETED = "completed";
    const REJECT = "reject";

    protected $guarded = [];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

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

        return (string)$reference;
    }
}