<?php

namespace Domain\Product\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Discount extends Model
{
    /** @use HasFactory<\Database\Factories\DiscountFactory> */
    use HasFactory;

    const TYPE_PERCENTAGE = "percentage";
    const TYPE_FIXED = "fixed";

    protected $guarded = [];

    protected $casts = [
        'active' => 'integer',
        'value' => 'decimal:2',
    ];

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Check if the discount is active and not expired.
     * @return bool
     */
    public function isValid(): bool
    {
        if (!$this->active) {
            return false;
        }

        if ($this->expire_date && $this->expire_date < now()) {
            return false;
        }

        return true;
    }

    /**
     * Calculate discount amount based on type.
     * @param float $amount
     * @return float
     */
    public function calculateDiscount(float $amount): float
    {
        if ($this->type === self::TYPE_PERCENTAGE) {
            return ($amount * $this->value) / 100;
        }

        return $this->value;
    }
}