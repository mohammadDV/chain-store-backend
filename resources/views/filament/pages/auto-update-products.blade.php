<x-filament-panels::page dir="rtl">
    <form wire:submit="previewProduct" class="space-y-6">
        {{ $this->form }}

        <div class="flex flex-wrap items-center gap-3">
            <x-filament::button type="submit" wire:loading.attr="disabled" wire:target="previewProduct,applyUpdate">
                <span wire:loading.remove wire:target="previewProduct">
                    {{ __('site.scraper_preview') }}
                </span>
                <span wire:loading wire:target="previewProduct">
                    {{ __('site.scraper_loading') }}
                </span>
            </x-filament::button>

            <x-filament::button
                color="success"
                type="button"
                wire:click="applyUpdate"
                wire:confirm="{{ __('site.scraper_apply_confirm') }}"
                :disabled="$preview === null"
                wire:loading.attr="disabled"
                wire:target="previewProduct,applyUpdate"
            >
                <span wire:loading.remove wire:target="applyUpdate">
                    {{ __('site.scraper_apply') }}
                </span>
                <span wire:loading wire:target="applyUpdate">
                    {{ __('site.scraper_loading') }}
                </span>
            </x-filament::button>
        </div>
    </form>

    @if ($preview)
        @php
            $actionLabels = [
                'create' => __('site.scraper_action_create'),
                'update' => __('site.scraper_action_update'),
                'refresh' => __('site.scraper_action_refresh'),
            ];
            $actionColors = [
                'create' => 'success',
                'update' => 'info',
                'refresh' => 'warning',
            ];
            $action = $preview['action'] ?? 'update';
            $product = $preview['product'] ?? [];
            $changes = $preview['changes'] ?? [];
            $images = $product['images'] ?? [];
        @endphp

        <div class="mt-8 space-y-6">
            <div class="flex flex-wrap items-center gap-3">
                <x-filament::badge :color="$actionColors[$action] ?? 'gray'">
                    {{ $actionLabels[$action] ?? $action }}
                </x-filament::badge>
                @if (!empty($preview['has_changes']))
                    <x-filament::badge color="warning">
                        {{ __('site.scraper_has_changes') }}
                    </x-filament::badge>
                @else
                    <x-filament::badge color="gray">
                        {{ __('site.scraper_no_changes') }}
                    </x-filament::badge>
                @endif
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                <x-filament::section :heading="__('site.scraper_incoming_product')">
                    <dl class="grid grid-cols-1 gap-3 text-sm">
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">{{ __('site.title') }}</dt>
                            <dd class="font-medium">{{ $product['title'] ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">{{ __('site.code') }}</dt>
                            <dd class="font-medium">{{ $product['code'] ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">{{ __('site.url') }}</dt>
                            <dd class="break-all">
                                @if (!empty($product['url']))
                                    <a href="{{ $product['url'] }}" target="_blank" rel="noopener noreferrer" class="text-primary-600 hover:underline">
                                        {{ $product['url'] }}
                                    </a>
                                @else
                                    —
                                @endif
                            </dd>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <dt class="text-gray-500 dark:text-gray-400">{{ __('site.scraper_field_price') }}</dt>
                                <dd class="font-medium">{{ number_format((int) ($product['price'] ?? 0)) }}</dd>
                            </div>
                            <div>
                                <dt class="text-gray-500 dark:text-gray-400">{{ __('site.discount') }}</dt>
                                <dd class="font-medium">{{ $product['discount'] ?? 0 }}%</dd>
                            </div>
                        </div>
                    </dl>

                    @if (!empty($images))
                        <div class="mt-4 flex flex-wrap gap-2">
                            @foreach (array_slice($images, 0, 8) as $image)
                                <img src="{{ $image }}" alt="" class="h-20 w-20 rounded-lg object-cover ring-1 ring-gray-950/10 dark:ring-white/10">
                            @endforeach
                        </div>
                    @endif
                </x-filament::section>

                <x-filament::section :heading="__('site.scraper_current_product')">
                    @if (!empty($preview['existing']))
                        @php $existing = $preview['existing']; @endphp
                        <dl class="grid grid-cols-1 gap-3 text-sm">
                            <div>
                                <dt class="text-gray-500 dark:text-gray-400">{{ __('site.title') }}</dt>
                                <dd class="font-medium">{{ $existing['title'] ?? '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-gray-500 dark:text-gray-400">{{ __('site.code') }}</dt>
                                <dd class="font-medium">{{ $existing['code'] ?? '—' }}</dd>
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <dt class="text-gray-500 dark:text-gray-400">{{ __('site.scraper_field_price') }}</dt>
                                    <dd class="font-medium">{{ number_format((int) ($existing['price'] ?? 0)) }}</dd>
                                </div>
                                <div>
                                    <dt class="text-gray-500 dark:text-gray-400">{{ __('site.discount') }}</dt>
                                    <dd class="font-medium">{{ $existing['discount'] ?? 0 }}%</dd>
                                </div>
                            </div>
                        </dl>
                    @else
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            {{ __('site.scraper_not_in_catalog') }}
                        </p>
                    @endif
                </x-filament::section>
            </div>

            <x-filament::section :heading="__('site.scraper_changes')">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 dark:border-white/10 text-right">
                                <th class="px-3 py-2 font-medium">{{ __('site.scraper_field') }}</th>
                                <th class="px-3 py-2 font-medium">{{ __('site.scraper_current_value') }}</th>
                                <th class="px-3 py-2 font-medium">{{ __('site.scraper_incoming_value') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($changes as $change)
                                @php
                                    $changed = !empty($change['changed']);
                                    $fieldKey = 'site.scraper_field_' . ($change['field'] ?? '');
                                    $fieldLabel = __($fieldKey);
                                    if ($fieldLabel === $fieldKey) {
                                        $fieldLabel = $change['field'] ?? '';
                                    }
                                @endphp
                                <tr @class([
                                    'border-b border-gray-100 dark:border-white/5',
                                    'bg-warning-50 dark:bg-warning-400/10' => $changed,
                                ])>
                                    <td class="px-3 py-2 font-medium whitespace-nowrap">{{ $fieldLabel }}</td>
                                    <td class="px-3 py-2 align-top">
                                        <pre class="whitespace-pre-wrap break-all font-sans text-xs">{{ $this->formatPreviewValue($change['current'] ?? null) }}</pre>
                                    </td>
                                    <td class="px-3 py-2 align-top">
                                        <pre class="whitespace-pre-wrap break-all font-sans text-xs">{{ $this->formatPreviewValue($change['incoming'] ?? null) }}</pre>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-filament::section>
        </div>
    @endif
</x-filament-panels::page>
