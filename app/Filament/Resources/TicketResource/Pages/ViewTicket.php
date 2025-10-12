<?php

namespace App\Filament\Resources\TicketResource\Pages;

use App\Filament\Resources\TicketResource;
use Domain\Notification\Services\NotificationService;
use Domain\Ticket\Models\TicketMessage;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Pages\ViewRecord;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ViewTicket extends ViewRecord
{
    protected static string $resource = TicketResource::class;

    protected static string $view = 'filament.resources.ticket-resource.pages.view-ticket';

    public ?array $data = [];

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\Action::make('changeStatus')
                ->label(__('site.change_status'))
                ->color('warning')
                ->form([
                    Forms\Components\Select::make('status')
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
        $ticket = $this->getRecord();
        TicketMessage::query()
            ->where('ticket_id', $ticket->id)
            ->where('user_id', $ticket->user_id)
            ->where('status', 'pending')
            ->update(['status' => 'read']);
    }

    public function form(Form $form): Form
    {
        $ticket = $this->getRecord();
        $isTicketClosed = $ticket && $ticket->status === 'closed';

        return $form
            ->schema([
                Forms\Components\Section::make(__('site.send_message'))
                    ->schema([
                        Forms\Components\Textarea::make('message')
                            ->label(__('site.ticket_message_content'))
                            ->required()
                            ->rows(4)
                            ->disabled($isTicketClosed)
                            ->helperText($isTicketClosed ? __('site.ticket_closed_no_messages') : ''),
                        Forms\Components\FileUpload::make('file')
                            ->label(__('site.ticket_message_attachment'))
                            ->disk('s3')
                            ->directory('/ticket-messages')
                            ->acceptedFileTypes(['image/*', 'application/pdf', 'text/*'])
                            ->maxSize(5120) // 5MB
                            ->disabled($isTicketClosed),
                    ])
                    ->columns(1)
                    ->visible(!$isTicketClosed),
            ]);
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
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
                            })
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'active' => __('site.active'),
                                'closed' => __('site.closed'),
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
                                    ->size(TextEntry\TextEntrySize::Small),
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
                                    })
                                    ->formatStateUsing(fn (string $state): string => match ($state) {
                                        'pending' => __('site.pending'),
                                        'read' => __('site.read'),
                                    }),
                                TextEntry::make('created_at')
                                    ->label(__('site.sent_at'))
                                    ->dateTime('Y/m/d H:i:s')
                                    ->size(TextEntry\TextEntrySize::Small),
                            ])
                            ->columns(5),
                    ]),
            ]);
    }

    public function sendMessage(): void
    {
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
            // $filePath = $this->data['file']->store('oshtow/ticket-messages', 's3', 'public');
            $filePath = Storage::disk('s3')->put('oshtow/ticket-messages', $this->data['file'], 'public');
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
        $record = $this->getRecord()->load(['user', 'subject', 'messages.user']);

        // Order messages by created_at in ascending order (oldest first)
        $record->messages = $record->messages->sortBy('created_at');

        return [
            'record' => $record,
        ];
    }
}
