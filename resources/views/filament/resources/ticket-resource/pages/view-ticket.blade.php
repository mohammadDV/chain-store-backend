<x-filament-panels::page dir="rtl">
    <div class="space-y-6">
        <!-- Ticket Information -->
        <x-filament::section>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="text-sm font-medium text-gray-500">{{ __('site.ticket_id') }}</label>
                    <p class="text-lg font-semibold">{{ $this->record->id }}</p>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-500">{{ __('site.ticket_user') }}</label>
                    <p class="text-lg">{{ $this->record->user->email }}</p>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-500">{{ __('site.ticket_subject') }}</label>
                    <p class="text-lg">{{ $this->record->subject->title }}</p>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-500">{{ __('site.ticket_status') }}</label>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $this->record->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                        {{ $this->record->status === 'active' ? __('site.active') : __('site.closed') }}
                    </span>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-500">{{ __('site.ticket_created_at') }}</label>
                    <p class="text-lg">{{ $this->record->created_at->format('Y/m/d H:i') }}</p>
                </div>
            </div>
        </x-filament::section>

        <!-- Messages -->
        <x-filament::section>
            <div class="space-y-4">
                <h3 class="text-lg font-medium">{{ __('site.ticket_messages') }}</h3>

                <div class="space-y-4 max-h-96 overflow-y-auto">
                    @foreach($this->record->messages()->orderBy('created_at')->get() as $message)
                        <div class="bg-gray-50 rounded-lg p-4 {{ $message->user_id === auth()->id() ? 'mr-8' : 'ml-8' }}">
                            <div class="flex justify-between items-start mb-2">
                                <div class="flex items-center space-x-2">
                                    <span class="text-sm font-medium">{{ $message->user->email }}</span>
                                    <span class="text-xs text-gray-500">{{ $message->created_at->format('Y/m/d H:i') }}</span>
                                </div>
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{ $message->status === 'read' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                    {{ $message->status === 'read' ? __('site.read') : __('site.pending') }}
                                </span>
                            </div>
                            <div class="text-gray-700 mb-2">
                                {{ $message->message }}
                            </div>
                            @if($message->file)
                                <div class="text-sm text-blue-600">
                                    <a href="{{ Storage::disk('s3')->url($message->file) }}" target="_blank" class="hover:underline">
                                        📎 {{ __('site.attachment') }}
                                    </a>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </x-filament::section>

        <!-- Send Message Form -->
        <x-filament::section>
            <form wire:submit.prevent="sendMessage" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('site.ticket_message_content') }}</label>
                    <textarea
                        wire:model="data.message"
                        class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                        rows="4"
                        placeholder="{{ __('site.type_message_placeholder') }}"
                    ></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('site.ticket_message_attachment') }} ({{ __('site.optional') }})</label>
                    <input
                        type="file"
                        wire:model="data.file"
                        class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                        accept="image/*,application/pdf,text/*"
                    >
                    <p class="text-xs text-gray-500 mt-1">{{ __('site.file_upload_help') }}</p>
                </div>

                <div class="flex justify-end">
                    <x-filament::button
                        type="submit"
                        color="primary"
                        size="lg"
                    >
                        {{ __('site.send_message') }}
                    </x-filament::button>
                </div>
            </form>
        </x-filament::section>
    </div>
</x-filament-panels::page>
