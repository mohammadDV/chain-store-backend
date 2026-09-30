@php
    $siteKey = config('services.recaptcha.site_key');
@endphp

<div
    wire:ignore
    x-data
    x-init="
        window.__filamentRecaptchaCallback = (token) => {
            $wire.set('data.token', token);
        };
        window.__filamentRecaptchaExpired = () => {
            $wire.set('data.token', '');
        };

        const boot = () => {
            if (typeof grecaptcha === 'undefined' || ! grecaptcha.render) {
                setTimeout(boot, 50);
                return;
            }

            const el = $refs.recaptcha;
            if (! el || el.dataset.rendered === '1') {
                return;
            }

            grecaptcha.render(el, {
                sitekey: @js($siteKey),
                callback: window.__filamentRecaptchaCallback,
                'expired-callback': window.__filamentRecaptchaExpired,
            });
            el.dataset.rendered = '1';
        };

        if (! document.getElementById('google-recaptcha-script')) {
            const script = document.createElement('script');
            script.id = 'google-recaptcha-script';
            script.src = 'https://www.google.com/recaptcha/api.js?hl=fa&render=explicit';
            script.async = true;
            script.defer = true;
            script.onload = boot;
            document.head.appendChild(script);
        } else {
            boot();
        }
    "
    class="my-4 flex justify-center"
>
    <div x-ref="recaptcha"></div>
</div>
