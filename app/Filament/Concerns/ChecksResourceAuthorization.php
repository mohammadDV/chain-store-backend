<?php

namespace App\Filament\Concerns;

use Domain\User\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

trait ChecksResourceAuthorization
{
    abstract protected static function permissionPrefix(): string;

    public static function canViewAny(): bool
    {
        $user = Auth::user();

        return $user instanceof User && $user->can(static::permissionPrefix().'.view');
    }

    public static function canCreate(): bool
    {
        $user = Auth::user();

        return $user instanceof User && $user->can(static::permissionPrefix().'.create');
    }

    public static function canView($record): bool
    {
        return $record instanceof Model && Gate::allows('view', $record);
    }

    public static function canEdit($record): bool
    {
        return $record instanceof Model && Gate::allows('update', $record);
    }

    public static function canDelete($record): bool
    {
        return $record instanceof Model && Gate::allows('delete', $record);
    }

    public static function canDeleteAny(): bool
    {
        $user = Auth::user();

        return $user instanceof User && $user->can(static::permissionPrefix().'.delete');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canViewAny();
    }
}
