<?php

namespace Domain\Product\Models;

use App\ProductAttribute;
use Database\Factories\ProductFactory;
use Domain\Brand\Models\Brand;
use Domain\Review\Models\Review;
use Domain\Setting\Services\SettingService;
use Domain\User\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;

class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    const PENDING = 'pending';

    const COMPLETED = 'completed';

    const REJECT = 'reject';

    protected $guarded = [];

    protected $casts = [
        'vip' => 'boolean',
        'active' => 'boolean',
        'priority' => 'integer',
        'related_products' => 'array',
    ];

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'category_product', 'product_id', 'category_id');
    }

    public function orders(): BelongsToMany
    {
        return $this->belongsToMany(Order::class, 'order_product', 'product_id', 'order_id')->withPivot('count', 'amount', 'status', 'color_id', 'size_id');
    }

    public function likes(): MorphMany
    {
        return $this->morphMany(Like::class, 'likeable', 'likeable_type', 'likeable_id');
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function color(): BelongsTo
    {
        return $this->belongsTo(Color::class);
    }

    public function attributes(): HasMany
    {
        return $this->hasMany(ProductAttribute::class);
    }

    /**
     * Get the files associated with the product.
     */
    public function files(): HasMany
    {
        return $this->hasMany(File::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    public function sizes(): HasMany
    {
        return $this->hasMany(Size::class);
    }

    public function getAmountAttribute($value)
    {
        $settingService = app(SettingService::class);
        $rate = $settingService->getExchangeRateWithFallback();

        $dicountRate = max(0, $this->discount);

        $amount = $value ?? 0;

        $payableAmount = $amount * $rate;

        $profit = $payableAmount * $settingService->getProfitRateWithFallback() / 100;

        if ($dicountRate > 0) {
            $amount = $amount * 100 / $this->discount;
        }

        return ceil(($payableAmount + $profit) / 1000) * 1000;
    }

    /**
     * Get the active plans
     *
     * @param  Builder  $builder  The query builder
     * @return Builder The query builder including the active statement
     */
    public function scopeActive(Builder $builder): Builder
    {
        return $builder->where('active', 1)
            ->whereHas('sizes', function ($query) {
                $query->where('status', 1)
                    ->whereHas('stock', function ($stockQuery) {
                        $stockQuery->where('quantity', '>', 0);
                    });
            })
            ->where('status', self::COMPLETED)
            ->where('is_failed', 0);
    }

    /**
     * Active completed products whose updated_at is older than the given threshold.
     */
    public function scopeStaleForRefresh(Builder $builder, Carbon|\DateTimeInterface|string $staleBefore): Builder
    {
        return $builder
            ->where('active', 1)
            ->where('status', self::COMPLETED)
            ->where('updated_at', '<', $staleBefore)
            ->whereNotNull('code')
            ->where('code', '!=', '')
            ->whereNotNull('brand_id');
    }

    protected static function newFactory(): ProductFactory
    {
        return ProductFactory::new();
    }
}
