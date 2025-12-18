<?php

namespace Domain\Product\Services\Brands;

/**
 * Enum for supported brands
 *
 * This enum provides type-safe brand identifiers and their corresponding service classes.
 * Add new brands by adding a new case and updating the getServiceClass() method.
 */
enum BrandEnum: string
{
    case ADIDAS = 'adidas';
    case DECATHLON = 'decathlon';
    // TODO: Add more brands as they are implemented
    // case POMA = 'poma';
    // case NIKE = 'nike';

    /**
     * Get the service class for this brand
     *
     * @return string The fully qualified class name of the brand service
     */
    public function getServiceClass(): string
    {
        return match ($this) {
            self::ADIDAS => AdidasBrandService::class,
            self::DECATHLON => DecathlonBrandService::class,
            // TODO: Add more brands as they are implemented
            // self::POMA => PomaBrandService::class,
            // self::NIKE => NikeBrandService::class,
        };
    }

    /**
     * Get the pagination parameter for the product list
     *
     * @return string The pagination parameter
     */
    public static function getProductListPaginationParam(string $slug): string
    {
        return match (self::fromSlugOrFail($slug)) {
            self::ADIDAS => 'start',
            self::DECATHLON => 'from',
        };
    }

    /**
     * Get the pagination skip value for the product list
     *
     * @return int The pagination skip value
     */
    public static function getProductListPaginationSkip(string $slug): int
    {
        return match (self::fromSlugOrFail($slug)) {
            self::ADIDAS => 48,
            self::DECATHLON => 40,
        };
    }

    /**
     * Get the brand enum from a slug string
     *
     * @param string $slug The brand slug
     * @return self|null The brand enum or null if not found
     */
    public static function fromSlug(string $slug): ?self
    {
        $slug = strtolower($slug);

        return self::tryFrom($slug);
    }

    /**
     * Get the brand enum from a slug string or throw exception
     *
     * @param string $slug The brand slug
     * @return self The brand enum
     * @throws \ValueError If the slug doesn't match any brand
     */
    public static function fromSlugOrFail(string $slug): self
    {
        $slug = strtolower($slug);

        return self::from($slug);
    }

    /**
     * Check if a slug is a valid brand
     *
     * @param string $slug The brand slug
     * @return bool True if the slug matches a brand
     */
    public static function isValidSlug(string $slug): bool
    {
        return self::fromSlug($slug) !== null;
    }

    /**
     * Get all brand slugs
     *
     * @return array<string> Array of brand slugs
     */
    public static function getAllSlugs(): array
    {
        return array_column(self::cases(), 'value');
    }
}
