<?php

namespace App\Filament\Support;

use Domain\Product\Models\Category;
use Domain\Product\Support\CategoryPathLabels;
use Filament\Forms\Components\Select;
use Filament\Tables\Filters\SelectFilter;

final class CategorySelect
{
    /**
     * Show breadcrumb labels on a category relationship Select.
     */
    public static function withPathLabels(Select $select): Select
    {
        return $select
            ->getOptionLabelFromRecordUsing(
                fn (Category $record): string => CategoryPathLabels::for($record)
            );
    }

    /**
     * Show breadcrumb labels on a category relationship SelectFilter.
     */
    public static function filterWithPathLabels(SelectFilter $filter): SelectFilter
    {
        return $filter
            ->getOptionLabelFromRecordUsing(
                fn (Category $record): string => CategoryPathLabels::for($record)
            );
    }
}
