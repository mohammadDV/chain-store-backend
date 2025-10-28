<?php

namespace Domain\Wallet\Models;

use Domain\User\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Wallet extends Model
{

    const IRR = 'IRR';

    protected $fillable = [
        'user_id',
        'balance',
        'currency',
        'status',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'status' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function walletTransaction(): HasMany
    {
        return $this->hasMany(WalletTransaction::class);
    }

    public function canWithdraw(float $amount): bool
    {
        return $this->balance >= $amount;
    }
}