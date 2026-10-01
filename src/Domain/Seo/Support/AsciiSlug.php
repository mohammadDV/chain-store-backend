<?php

namespace Domain\Seo\Support;

use Illuminate\Support\Str;

/**
 * ASCII-only URL slugs: spaces become hyphens; Persian / non-English is rejected at validation.
 */
final class AsciiSlug
{
    public static function normalize(string $value): string
    {
        $value = trim(mb_strtolower($value, 'UTF-8'));
        $value = preg_replace('/[\s_]+/u', '-', $value) ?? '';
        $value = preg_replace('/-+/', '-', $value) ?? '';

        return trim($value, '-');
    }

    public static function generate(string $source, string $separator = '-'): string
    {
        return (string) Str::slug($source, $separator);
    }

    public static function containsNonAscii(string $value): bool
    {
        return (bool) preg_match('/[^\x00-\x7F]/', $value);
    }

    public static function isValid(string $value): bool
    {
        return (bool) preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $value);
    }
}
