@php
    $path = $getState();
    $imageUrl = $path;

    if ($path && ! str_starts_with($path, 'https://') && ! str_starts_with($path, 'http://')) {
        $imageUrl = \Storage::disk('s3')->url($path);
    }
@endphp

@if($path)
    <a
        href="{{ $imageUrl }}"
        target="_blank"
        rel="noopener noreferrer"
        style="display:inline-flex;line-height:0;"
    >
        <img
            src="{{ $imageUrl }}"
            alt=""
            width="40"
            height="40"
            loading="lazy"
            style="width:40px;height:40px;max-width:40px;max-height:40px;object-fit:cover;border-radius:6px;border:1px solid #e5e7eb;display:block;"
        />
    </a>
@else
    <span style="color:#9ca3af;">-</span>
@endif
