<?php

namespace Application\Api\Seo\Controllers;

use Core\Http\Controllers\Controller;
use Domain\Seo\Services\SeoCacheService;
use Domain\Setting\Services\SettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class SeoController extends Controller
{
    public function settings(SettingService $settingService): JsonResponse
    {
        return response()->json([
            'status' => 1,
            'data' => $settingService->getSeoSettings(),
        ], Response::HTTP_OK);
    }

    public function sitemap(SeoCacheService $seoCache): JsonResponse
    {
        return response()->json([
            'status' => 1,
            'data' => $seoCache->getSitemap(),
        ], Response::HTTP_OK);
    }

    public function redirects(SeoCacheService $seoCache): JsonResponse
    {
        return response()->json([
            'status' => 1,
            'data' => $seoCache->getRedirects(),
        ], Response::HTTP_OK);
    }
}
