<?php

namespace Domain\Product\Services\Brands;

use Domain\Brand\Models\Brand;

/**
 * Factory class to resolve the appropriate brand service based on brand
 *
 * This factory uses BrandEnum to map brand slugs to their corresponding service implementations.
 * Add new brands by adding them to BrandEnum.
 */
class BrandServiceFactory
{
    /**
     * Get the appropriate brand service for the given brand
     *
     * @param Brand $brand The brand model
     * @return BrandServiceInterface The brand service instance
     * @throws \Exception If no service is found for the brand
     */
    public static function getService(Brand $brand): BrandServiceInterface
    {
        $slug = strtolower($brand->slug ?? '');

        $brandEnum = BrandEnum::fromSlug($slug);

        if ($brandEnum === null) {
            throw new \Exception("No brand service found for brand: {$brand->title} (slug: {$slug}). Please implement a service class or add it to BrandEnum.");
        }

        $serviceClass = $brandEnum->getServiceClass();

        if (!class_exists($serviceClass)) {
            throw new \Exception("Brand service class {$serviceClass} does not exist.");
        }

        return new $serviceClass();
    }

    /**
     * Get the appropriate brand service by brand slug
     *
     * @param string $slug The brand slug
     * @return BrandServiceInterface The brand service instance
     * @throws \Exception If no service is found for the slug
     */
    public static function getServiceBySlug(string $slug): BrandServiceInterface
    {
        $brandEnum = BrandEnum::fromSlug($slug);

        if ($brandEnum === null) {
            throw new \Exception("No brand service found for slug: {$slug}");
        }

        $serviceClass = $brandEnum->getServiceClass();

        if (!class_exists($serviceClass)) {
            throw new \Exception("Brand service class {$serviceClass} does not exist.");
        }

        return new $serviceClass();
    }

    /**
     * Get the appropriate brand service by BrandEnum
     *
     * @param BrandEnum $brandEnum The brand enum
     * @return BrandServiceInterface The brand service instance
     * @throws \Exception If the service class does not exist
     */
    public static function getServiceByEnum(BrandEnum $brandEnum): BrandServiceInterface
    {
        $serviceClass = $brandEnum->getServiceClass();

        if (!class_exists($serviceClass)) {
            throw new \Exception("Brand service class {$serviceClass} does not exist.");
        }

        return new $serviceClass();
    }
}
