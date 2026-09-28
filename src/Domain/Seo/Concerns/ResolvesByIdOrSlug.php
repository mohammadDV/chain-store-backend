<?php

namespace Domain\Seo\Concerns;

use Illuminate\Database\Eloquent\Model;

trait ResolvesByIdOrSlug
{
    /**
     * Resolve route binding by primary key or slug.
     */
    public function resolveRouteBinding($value, $field = null): ?Model
    {
        if ($field !== null) {
            return $this->where($field, $value)->first();
        }

        return $this->newQuery()
            ->where(function ($query) use ($value) {
                $query->where($this->getRouteKeyName(), $value)
                    ->orWhere('slug', $value);
            })
            ->first();
    }
}
