<?php

namespace Domain\Payment\Models;

use Domain\Wallet\Models\Wallet;
use Domain\User\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    const PENDING = 'pending';
    const COMPLETED = 'completed';
    const CANCELLED = 'cancelled';
    const FAILED = 'failed';
    const WALLET = 'wallet';
    const BANK = 'bank';
    const ORDER = 'order';


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
        return md5('sys#65687' . $id . '$#$rstg@3');
    }

    /**
     * Calculate revenue based on transaction type and amount
     */
    public function getRevenueAttribute(): float
    {
        return match ($this->model_type) {
            self::WALLET => $this->amount * 0.10,  // 10% from WALLET
            self::PLAN => $this->amount * 1.00,    // 100% from PLAN
            self::IDENTITY => ($this->amount * 1.00) - 15000, // 100% from IDENTITY
            self::SECURE => $this->amount * 0.10,   // 10% from SECURE
            default => 0,
        };
    }

    /**
     * Get monthly revenue for a specific month (only completed transactions)
     */
    public static function getMonthlyRevenue(int $year, int $month): float
    {
        return static::whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->where('status', self::COMPLETED)
            ->get()
            ->sum('revenue');
    }

    /**
     * Get revenue grouped by month for a specific year (only completed transactions)
     */
    public static function getRevenueByMonth(int $year): array
    {
        $revenue = [];

        for ($month = 1; $month <= 12; $month++) {
            $revenue[$month] = static::getMonthlyRevenue($year, $month);
        }

        return $revenue;
    }

    /**
     * Get total revenue for completed transactions only
     */
    public static function getTotalRevenue(): float
    {
        return static::where('status', self::COMPLETED)
            ->get()
            ->sum('revenue');
    }

    /**
     * Get revenue by transaction type for completed transactions only
     */
    public static function getRevenueByType(): array
    {
        $revenue = [];

        foreach ([self::WALLET, self::PLAN, self::IDENTITY, self::SECURE] as $type) {
            $revenue[$type] = static::where('model_type', $type)
                ->where('status', self::COMPLETED)
                ->get()
                ->sum('revenue');
        }

        return $revenue;
    }
}