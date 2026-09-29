<?php

namespace Application\Api\Setting\Controllers;

use Core\Http\Controllers\Controller;
use Domain\Setting\Services\SettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class SettingController extends Controller
{
    public function features(SettingService $settingService): JsonResponse
    {
        return response()->json([
            'status' => 1,
            'data' => $settingService->getPublicFeatures(),
        ], Response::HTTP_OK);
    }
}
