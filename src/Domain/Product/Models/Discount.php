<?php

namespace Domain\Product\Models;

use Database\Factories\DiscountFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Discount extends Model
{
    /** @use HasFactory<DiscountFactory> */
    use HasFactory;

    const TYPE_PERCENTAGE = 'percentage';

    const TYPE_FIXED = 'fixed';

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
     */
    public function isValid(): bool
    {
        if (! $this->active) {
            return false;
        }

        if ($this->expire_date && $this->expire_date < Carbon::now()) {
            return false;
        }

        return true;
    }

    /**
     * Calculate discount amount based on type.
     */
    public function calculateDiscount(float $amount): float
    {
        if ($this->type === self::TYPE_PERCENTAGE) {

            $discountAmount = ($amount * $this->value) / 100;

            if (! empty($this->max_value) && intval($this->max_value) > 0) {
                $discountAmount = $discountAmount > $this->max_value ? $this->max_value : $discountAmount;
            }

            return (float) $discountAmount;
        }

        return (float) $this->value;
    }

    protected static function newFactory(): DiscountFactory
    {
        return DiscountFactory::new();
    }
}
