<?php

use Domain\Seo\Support\AsciiSlug;

it('normalizes spaces and underscores to hyphens', function () {
    expect(AsciiSlug::normalize('Product Name'))->toBe('product-name')
        ->and(AsciiSlug::normalize('Product__Name  Test'))->toBe('product-name-test')
        ->and(AsciiSlug::normalize('  Hello World  '))->toBe('hello-world')
        ->and(AsciiSlug::normalize('already-slug'))->toBe('already-slug');
});

it('generates ascii slug from english title', function () {
    expect(AsciiSlug::generate('Cool Product 2024'))->toBe('cool-product-2024');
});

it('generates ascii slug from persian title via transliteration', function () {
    $slug = AsciiSlug::generate('محصول تست');

    expect($slug)->not->toBe('')
        ->and(AsciiSlug::containsNonAscii($slug))->toBeFalse()
        ->and(AsciiSlug::isValid($slug))->toBeTrue();
});

it('detects non-ascii characters', function () {
    expect(AsciiSlug::containsNonAscii('product-name'))->toBeFalse()
        ->and(AsciiSlug::containsNonAscii('محصول'))->toBeTrue()
        ->and(AsciiSlug::containsNonAscii('product-محصول'))->toBeTrue();
});

it('validates ascii slug format', function () {
    expect(AsciiSlug::isValid('product-name'))->toBeTrue()
        ->and(AsciiSlug::isValid('a'))->toBeTrue()
        ->and(AsciiSlug::isValid('product--name'))->toBeFalse()
        ->and(AsciiSlug::isValid('-product'))->toBeFalse()
        ->and(AsciiSlug::isValid('Product'))->toBeFalse()
        ->and(AsciiSlug::isValid(''))->toBeFalse();
});
