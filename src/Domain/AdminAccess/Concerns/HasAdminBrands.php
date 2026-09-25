<?php

namespace Domain\AdminAccess\Concerns;

use Domain\Brand\Models\Brand;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

trait HasAdminBrands
{
    /**
     * @return BelongsToMany<Brand, $this>
     */
    public function adminBrands(): BelongsToMany
    {
        return $this->belongsToMany(Brand::class, 'admin_brand')
            ->withTimestamps();
    }
}
