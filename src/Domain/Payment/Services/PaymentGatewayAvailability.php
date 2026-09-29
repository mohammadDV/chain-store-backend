<?php

namespace Domain\Payment\Services;

use Domain\Setting\Services\SettingService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Single gate for online payment (bank gateway) availability.
 * Reads from the cached settings singleton — O(1) after warm cache.
 */
final class PaymentGatewayAvailability
{
    public function __construct(
        private SettingService $settingService,
    ) {}

    public function isEnabled(): bool
    {
        return $this->settingService->isPaymentGatewayEnabled();
    }

    public function disabledMessage(): string
    {
        return __('site.payment_gateway_disabled');
    }

    public function disabledJsonResponse(): JsonResponse
    {
        return response()->json([
            'status' => 0,
            'message' => $this->disabledMessage(),
            'payment_gateway_enabled' => false,
        ], Response::HTTP_BAD_REQUEST);
    }
}
