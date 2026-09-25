<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Domain\AdminAccess\AdminPermission;
use Domain\AdminAccess\Services\AdminAccessService;
use Domain\Brand\Models\Brand;
use Domain\User\Models\User;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;

/**
 * @property-read Schema $form
 */
class ManageUserPermissions extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = UserResource::class;

    protected string $view = 'filament.resources.user-resource.pages.manage-user-permissions';

    public User $user;

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public static function canAccess(array $parameters = []): bool
    {
        $authUser = auth()->user();

        return $authUser instanceof User && $authUser->isSuperAdmin();
    }

    public function mount(int|string $record): void
    {
        abort_unless(static::canAccess(), 403);

        $this->user = User::query()->findOrFail($record);
        $this->user->load(['permissions', 'adminBrands']);

        $modulePermissions = [];
        $owned = $this->user->getPermissionNames()->all();

        foreach (AdminPermission::grouped() as $module => $permissions) {
            $modulePermissions[$module] = array_values(array_intersect($owned, $permissions));
        }

        $this->form->fill([
            'module_permissions' => $modulePermissions,
            'brand_ids' => $this->user->adminBrands->pluck('id')->all(),
        ]);
    }

    public function getTitle(): string|Htmlable
    {
        return __('site.manage_permissions').': '.$this->user->getFilamentName();
    }

    public function form(Schema $schema): Schema
    {
        $permissionSections = [];

        foreach (AdminPermission::grouped() as $module => $permissions) {
            $permissionSections[] = Section::make(__('site.permission_module_'.$module))
                ->schema([
                    CheckboxList::make('module_permissions.'.$module)
                        ->hiddenLabel()
                        ->options(collect($permissions)->mapWithKeys(
                            fn (string $permission) => [$permission => __('site.permission_'.$permission)]
                        )->all())
                        ->columns(2)
                        ->bulkToggleable(),
                ])
                ->collapsible();
        }

        return $schema
            ->components([
                Section::make(__('site.admin_brands'))
                    ->description(__('site.admin_brands_help'))
                    ->schema([
                        Select::make('brand_ids')
                            ->label(__('site.brands'))
                            ->multiple()
                            ->options(fn () => Brand::query()->orderBy('title')->pluck('title', 'id'))
                            ->searchable()
                            ->preload(),
                    ]),
                ...$permissionSections,
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        abort_unless(static::canAccess(), 403);

        $state = $this->form->getState();
        $modulePermissions = $state['module_permissions'] ?? [];
        $permissions = [];

        foreach ($modulePermissions as $selected) {
            if (is_array($selected)) {
                $permissions = array_merge($permissions, $selected);
            }
        }

        app(AdminAccessService::class)->syncForUser(
            $this->user,
            array_values(array_unique($permissions)),
            $state['brand_ids'] ?? [],
        );

        Notification::make()
            ->title(__('site.permissions_updated_successfully'))
            ->success()
            ->send();
    }
}
