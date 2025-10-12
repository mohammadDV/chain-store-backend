@php
    $fileUrl = \Storage::disk('s3')->url($path);
    $fileExtension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    $isImage = in_array($fileExtension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg']);
    $isVideo = in_array($fileExtension, ['mp4', 'avi', 'mov', 'wmv', 'webm']);
@endphp

<div class="flex items-center justify-center w-16 h-16">
    @if($isImage)
        <img src="{{ $fileUrl }}"
             alt="File preview"
             class="w-full h-full object-cover rounded-lg border border-gray-200"
             style="max-width: 64px; max-height: 64px;">
    @elseif($isVideo)
        <video class="w-full h-full object-cover rounded-lg border border-gray-200"
               style="max-width: 64px; max-height: 64px;"
               controls>
            <source src="{{ $fileUrl }}" type="video/{{ $fileExtension }}">
            Your browser does not support the video tag.
        </video>
    @else
        <div class="w-full h-full flex items-center justify-center bg-gray-100 rounded-lg border border-gray-200">
            <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
        </div>
    @endif
</div>
