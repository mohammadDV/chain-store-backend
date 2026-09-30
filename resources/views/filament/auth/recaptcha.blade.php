@php
    $siteKey = $siteKey ?? config('services.recaptcha.site_key');
@endphp

<div wire:ignore>
    @if (filled($siteKey))
        <script src="https://www.google.com/recaptcha/enterprise.js?render={{ urlencode($siteKey) }}" async defer></script>
    @endif
</div>
