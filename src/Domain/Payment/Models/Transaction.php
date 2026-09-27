<?php

namespace Domain\Payment\Models;

use Database\Factories\TransactionFactory;
use Domain\User\Models\User;
use Domain\Wallet\Models\Wallet;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    /** @use HasFactory<TransactionFactory> */
    use HasFactory;

    const PENDING = 'pending';

    const COMPLETED = 'completed';

    const CANCELLED = 'cancelled';

    const FAILED = 'failed';

    const WALLET = 'wallet';

    const BANK = 'bank';

    const ORDER = 'order';

    const IDENTITY = 'identity';

    protected $fillable = [
        'model_id',
        'model_type',
        'user_id',
        'amount',
        'status',
        'reference',
        'bank_transaction_id',
        'description',
        'message',
        'manual',
        'image',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public static function generateHash(string $id): string
    {
        return md5('sys#65687'.$id.'$#$rstg@3');
    }

    protected static function newFactory(): TransactionFactory
    {
        return TransactionFactory::new();
    }
}
