<?php

namespace App\Filament\Resources\NotificationResource\Pages;

use App\Filament\Resources\NotificationResource;
use Domain\User\Models\User;
use Domain\Notification\Services\NotificationService;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification as FilamentNotification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class ListNotifications extends ListRecords
{
    protected static string $resource = NotificationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('send_notification')
                ->label(__('site.send_notification'))
                ->icon('heroicon-o-paper-airplane')
                ->color('primary')
                ->form([
                    Section::make(__('site.notification_content'))
                        ->schema([
                            TextInput::make('title')
                                ->label(__('site.title'))
                                ->required()
                                ->maxLength(255)
                                ->placeholder(__('site.enter_notification_title')),
                            Textarea::make('content')
                                ->label(__('site.content'))
                                ->required()
                                ->rows(5)
                                ->placeholder(__('site.enter_notification_content')),
                        ]),
                    Section::make(__('site.recipients'))
                        ->schema([
                            Radio::make('recipient_type')
                                ->label(__('site.send_to'))
                                ->options([
                                    'all' => __('site.all_users'),
                                    'specific' => __('site.specific_users'),
                                    'active' => __('site.active_users_only'),
                                    'verified' => __('site.verified_users_only'),
                                ])
                                ->default('all')
                                ->reactive()
                                ->required(),
                            Select::make('users')
                                ->label(__('site.select_users'))
                                ->multiple()
                                ->options(User::all()->mapWithKeys(function ($user) {
                                    return [$user->id => $user->getFilamentName()];
                                }))
                                ->searchable()
                                ->preload()
                                ->visible(fn (Forms\Get $get): bool => $get('recipient_type') === 'specific')
                                ->required(fn (Forms\Get $get): bool => $get('recipient_type') === 'specific'),
                        ]),
                    Section::make(__('site.email_settings'))
                        ->schema([
                            Toggle::make('send_email')
                                ->label(__('site.send_email_notification'))
                                ->default(true)
                                ->helperText(__('site.send_email_helper')),
                        ]),
                ])
                ->action(function (array $data): void {
                    // Get users based on recipient type
                    $users = $this->getUsers($data['recipient_type'], $data['users'] ?? []);

                    if ($users->isEmpty()) {
                        FilamentNotification::make()
                            ->title(__('site.no_users_found'))
                            ->warning()
                            ->send();
                        return;
                    }

                    $notificationData = [
                        'title' => $data['title'],
                        'content' => $data['content'],
                    ];

                    $sentCount = 0;
                    foreach ($users as $user) {
                        try {
                            NotificationService::create($notificationData, $user, $data['send_email'] ?? true);
                            $sentCount++;
                        } catch (\Exception $e) {
                            // Log error but continue with other users
                            Log::error('Failed to send notification to user ' . $user->id . ': ' . $e->getMessage());
                        }
                    }

                    FilamentNotification::make()
                        ->title(__('site.notifications_sent_successfully', ['count' => $sentCount]))
                        ->success()
                        ->send();
                })
                ->modalHeading(__('site.send_notification'))
                ->modalSubmitActionLabel(__('site.send_notification'))
                ->modalWidth('3xl'),
        ];
    }

    protected function getUsers(string $recipientType, array $specificUsers = []): Collection
    {
        $query = User::query();

        switch ($recipientType) {
            case 'all':
                return $query->get();

            case 'specific':
                return $query->whereIn('id', $specificUsers)->get();

            case 'active':
                return $query->where('status', 1)->get();

            case 'verified':
                return $query->whereNotNull('email_verified_at')->get();

            default:
                return collect();
        }
    }
}
