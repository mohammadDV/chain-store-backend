<?php

namespace App\Filament\Filters;

use Filament\Forms\Components\TextInput;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;

final class UserIdFilter
{
    /**
     * Exact user_id filter — no user list preload (safe for large user tables).
     *
     * @param  null|callable(Builder, int): Builder  $constrain
     */
    public static function make(?callable $constrain = null): Filter
    {
        return Filter::make('user_id')
            ->label(__('site.user_id'))
            ->schema([
                TextInput::make('value')
                    ->label(__('site.user_id'))
                    ->numeric(),
            ])
            ->query(function (Builder $query, array $data) use ($constrain): Builder {
                $userId = $data['value'] ?? null;

                if (! filled($userId)) {
                    return $query;
                }

                $userId = (int) $userId;

                if ($constrain) {
                    return $constrain($query, $userId);
                }

                return $query->where($query->getModel()->getTable().'.user_id', $userId);
            })
            ->indicateUsing(function (array $data): ?string {
                return filled($data['value'] ?? null)
                    ? __('site.user_id').': '.$data['value']
                    : null;
            });
    }
}
