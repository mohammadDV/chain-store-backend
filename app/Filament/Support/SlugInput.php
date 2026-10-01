<?php

namespace App\Filament\Support;

use Domain\Seo\Support\AsciiSlug;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Set;

final class SlugInput
{
    public static function configure(TextInput $input, bool $required = false): TextInput
    {
        $input = $input
            ->label(__('site.slug'))
            ->maxLength(255)
            ->unique(ignoreRecord: true)
            ->helperText(__('site.slug_ascii_help'))
            ->live(onBlur: true)
            ->afterStateUpdated(function (?string $state, Set $set): void {
                if (! filled($state)) {
                    return;
                }

                $set('slug', AsciiSlug::normalize($state));
            })
            ->dehydrateStateUsing(function (?string $state): ?string {
                if (! filled($state)) {
                    return $state;
                }

                return AsciiSlug::normalize($state);
            })
            ->rules([
                fn (): \Closure => function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! filled($value)) {
                        return;
                    }

                    $raw = (string) $value;
                    if (AsciiSlug::containsNonAscii($raw)) {
                        $fail(__('site.slug_must_be_english'));

                        return;
                    }

                    $normalized = AsciiSlug::normalize($raw);
                    if ($normalized === '' || ! AsciiSlug::isValid($normalized)) {
                        $fail(__('site.slug_invalid_format'));
                    }
                },
            ]);

        if ($required) {
            $input = $input->required();
        }

        return $input;
    }
}
