<?php

namespace Domain\Product\Models;

use App\ProductAttribute;
use Domain\Brand\Models\Brand;
use Domain\Product\Models\Category;
use Domain\Product\Models\File;
use Domain\Review\Models\Review;
use Domain\Setting\Services\SettingService;
use Domain\User\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    /** @use HasFactory<\Database\Factories\ProductFactory> */
    use HasFactory;

    const PENDING = "pending";
    const COMPLETED = "completed";
    const REJECT = "reject";

    protected $guarded = [];

    protected $casts = [
        'vip' => 'boolean',
        'active' => 'boolean',
        'priority' => 'integer',
        'related_products' => 'array',
    ];

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'category_product', 'product_id', 'category_id');
    }

    public function orders()
    {
        return $this->belongsToMany(Order::class, 'order_product', 'product_id', 'order_id')->withPivot('count', 'amount', 'status', 'color_id', 'size_id');
    }

    public function likes()
    {
        return $this->morphMany(Like::class, 'likeable', 'likeable_type', 'likeable_id');
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function color()
    {
        return $this->belongsTo(Color::class);
    }

    public function attributes()
    {
        return $this->hasMany(ProductAttribute::class);
    }

    /**
     * Get the files associated with the product.
     */
    public function files()
    {
        return $this->hasMany(File::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function favorites()
    {
        return $this->hasMany(Favorite::class);
    }

    public function sizes()
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
}