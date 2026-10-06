<?php

namespace Domain\Brand\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * @property int $id
 * @property string $title
 * @property string|null $link
 * @property string|null $image
 * @property int $status
 * @property int $priority
 * @property int|null $brand_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Brand|null $brand
 */
class Banner extends Model
{
    protected $guarded = [];

    protected static function booted(): void
    {
        $clear = static function (Banner $banner): void {
            Cache::forget('banners:home');
            if ($banner->brand_id) {
                Cache::forget('banners:'.$banner->brand_id);
            }
        };

        static::saved($clear);
        static::deleted($clear);
    }

    /**
     * @return BelongsTo<Brand, $this>
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }
}
