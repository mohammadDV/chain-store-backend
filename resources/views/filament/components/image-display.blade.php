@php
    $state = $getState();
@endphp

@if($state)
    <div class="space-y-2">
        <div class="relative group">
            <img
                src="{{ $state }}"
                alt="{{ __('site.image') }}"
                class="w-full h-48 object-cover rounded-lg border border-gray-300 shadow-sm"
                loading="lazy"
            />
            <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-10 transition-all duration-200 rounded-lg flex items-center justify-center">
                <a
                    href="{{ $state }}"
                    target="_blank"
                    class="opacity-0 group-hover:opacity-100 transition-opacity duration-200 bg-white bg-opacity-90 text-gray-800 px-3 py-1 rounded-md text-sm font-medium hover:bg-opacity-100"
                >
                    {{ __('site.view_full_size') }}
                </a>
            </div>
        </div>
        <div class="text-xs text-gray-500">
            <a
                href="{{ $state }}"
                target="_blank"
                class="text-blue-600 hover:text-blue-800 underline"
            >
                {{ __('site.open_in_new_tab') }}
            </a>
        </div>
    </div>
@else
    <div class="text-gray-500 text-sm">
        {{ __('site.no_image_uploaded') }}
    </div>
@endif
