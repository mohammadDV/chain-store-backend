<?php

namespace App\Filament\Resources\TicketResource\Pages;

use App\Filament\Resources\TicketResource;
use Domain\Notification\Services\NotificationService;
use Domain\Ticket\Models\Ticket;
use Domain\Ticket\Models\TicketMessage;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\TextSize;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ViewTicket extends ViewRecord
{
    protected static string $resource = TicketResource::class;

    protected string $view = 'filament.resources.ticket-resource.pages.view-ticket';

    public ?array $data = [];

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            Action::make('changeStatus')
                ->label(__('site.change_status'))
                ->color('warning')
                ->schema([
                    Select::make('status')
                        ->label(__('site.new_status'))
                        ->options([
                            'active' => __('site.active'),
                            'closed' => __('site.closed'),
                        ])
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $this->changeStatus($data['status']);
                }),
        ];
    }

    public function mount(int|string $record): void
    {
        parent::mount($record);
        $this->data = [
            'message' => '',
            'file' => null,
        ];

        // Mark all pending messages as read when admin opens the ticket view
        /** @var Ticket $ticket */
        $ticket = $this->getRecord();
        TicketMessage::query()
            ->where('ticket_id', $ticket->id)
            ->where('user_id', $ticket->user_id)
            ->where('status', 'pending')
            ->update(['status' => 'read']);
    }

    public function form(Schema $schema): Schema
    {
        /** @var Ticket $ticket */
        $ticket = $this->getRecord();
        $isTicketClosed = $ticket->status === 'closed';

        return $schema
            ->components([
                Section::make(__('site.send_message'))
                    ->schema([
                        Textarea::make('message')
                            ->label(__('site.ticket_message_content'))
                            ->required()
                            ->rows(4)
                            ->disabled($isTicketClosed)
                            ->helperText($isTicketClosed ? __('site.ticket_closed_no_messages') : ''),
                        FileUpload::make('file')
                            ->label(__('site.ticket_message_attachment'))
                            ->disk('s3')
                            ->directory('/ticket-messages')
                            ->acceptedFileTypes(['image/*', 'application/pdf', 'text/*'])
                            ->maxSize(5120) // 5MB
                            ->disabled($isTicketClosed),
                    ])
                    ->columns(1)
                    ->visible(! $isTicketClosed),
            ]);
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('site.ticket_information'))
                    ->schema([
                        TextEntry::make('id')
                            ->label(__('site.ticket_id')),
                        TextEntry::make('user.email')
                            ->label(__('site.ticket_user')),
                        TextEntry::make('subject.title')
                            ->label(__('site.ticket_subject')),
                        TextEntry::make('status')
                            ->label(__('site.ticket_status'))
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'active' => 'success',
                                'closed' => 'danger',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'active' => __('site.active'),
                                'closed' => __('site.closed'),
                                default => $state,
                            }),
                        TextEntry::make('created_at')
                            ->label(__('site.ticket_created_at'))
                            ->dateTime('Y/m/d H:i:s'),
                    ])
                    ->columns(3),

                Section::make(__('site.ticket_messages'))
                    ->schema([
                        RepeatableEntry::make('messages')
                            ->schema([
                                TextEntry::make('user.email')
                                    ->label(__('site.from'))
                                    ->size(TextSize::Small),
                                TextEntry::make('message')
                                    ->label(__('site.ticket_message_content'))
                                    ->markdown(),
                                TextEntry::make('file')
                                    ->label(__('site.ticket_message_attachment'))
                                    ->formatStateUsing(fn ($state) => $state ? __('site.message_has_attachment') : __('site.message_no_attachment')),
                                TextEntry::make('status')
                                    ->label(__('site.ticket_message_status'))
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'pending' => 'warning',
                                        'read' => 'success',
                                        default => 'gray',
                                    })
                                    ->formatStateUsing(fn (string $state): string => match ($state) {
                                        'pending' => __('site.pending'),
                                        'read' => __('site.read'),
                                        default => $state,
                                    }),
                                TextEntry::make('created_at')
                                    ->label(__('site.sent_at'))
                                    ->dateTime('Y/m/d H:i:s')
                                    ->size(TextSize::Small),
                            ])
                            ->columns(5),
                    ]),
            ]);
    }

    public function sendMessage(): void
    {
        /** @var Ticket $ticket */
        $ticket = $this->getRecord();

        // Check if ticket is closed
        if ($ticket->status === 'closed') {
            Notification::make()
                ->title(__('site.error'))
                ->body(__('site.cannot_send_message_to_closed_ticket'))
                ->danger()
                ->send();

            return;
        }

        if (empty($this->data['message'])) {
            Notification::make()
                ->title(__('site.error'))
                ->body(__('site.message_required'))
                ->danger()
                ->send();

            return;
        }

        $filePath = null;

        // Handle file upload
        if ($this->data['file'] instanceof TemporaryUploadedFile) {
            // $filePath = $this->data['file']->store('boofstore/ticket-messages', 's3', 'public');
            $filePath = Storage::disk('s3')->put('boofstore/ticket-messages', $this->data['file'], 'public');
        }

        TicketMessage::create([
            'ticket_id' => $ticket->id,
            'user_id' => Auth::id(),
            'message' => $this->data['message'],
            'file' => $filePath,
            'status' => TicketMessage::PENDING,
        ]);

        // Mark all other messages as read
        $ticket->messages()->where('user_id', '!=', Auth::id())->update(['status' => 'read']);

        // Reset form
        $this->data = [
            'message' => '',
            'file' => null,
        ];

        NotificationService::create([
            'title' => __('site.ticket_message_sent_successfully_admin'),
            'content' => __('site.ticket_message_sent_successfully_message_admin_content'),
            'id' => $ticket->id,
            'type' => NotificationService::TICKET,
        ], $ticket->user);

        Notification::make()
            ->title(__('site.success'))
            ->body(__('site.message_sent_successfully'))
            ->success()
            ->send();
    }

    public function changeStatus(string $status): void
    {
        /** @var Ticket $ticket */
        $ticket = $this->getRecord();
        $ticket->update(['status' => $status]);

        Notification::make()
            ->title(__('site.status_updated'))
            ->body(__('site.ticket_status_changed', ['status' => __("site.{$status}")]))
            ->success()
            ->send();
    }

    protected function getViewData(): array
    {
        /** @var Ticket $record */
        $record = $this->getRecord()->load(['user', 'subject', 'messages.user']);

        // Order messages by created_at in ascending order (oldest first)
        $record->setRelation('messages', $record->messages->sortBy('created_at')->values());

        return [
            'record' => $record,
        ];
    }
}
