<?php

namespace Application\Api\User\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;
use Illuminate\Translation\PotentiallyTranslatedString;

class Recaptcha implements ValidationRule
{
    public function __construct(
        private readonly ?string $expectedAction = null,
    ) {}

    /**
     * Run the validation rule.
     *
     * @param  Closure(string): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $gResponseToken = is_string($value) ? trim($value) : '';

        if ($gResponseToken === '') {
            $fail(trans('site.Invalid recaptcha'));

            return;
        }

        $projectId = (string) config('services.recaptcha.project_id');
        $apiKey = (string) config('services.recaptcha.api_key');

        if ($projectId !== '' && $apiKey !== '') {
            if (! $this->validateEnterpriseAssessment($gResponseToken, $projectId, $apiKey)) {
                $fail(trans('site.Invalid recaptcha'));
            }

            return;
        }

        if (! $this->validateLegacySiteVerify($gResponseToken)) {
            $fail(trans('site.Invalid recaptcha'));
        }
    }

    private function validateEnterpriseAssessment(string $token, string $projectId, string $apiKey): bool
    {
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post(
            sprintf(
                'https://recaptchaenterprise.googleapis.com/v1/projects/%s/assessments?key=%s',
                rawurlencode($projectId),
                rawurlencode($apiKey),
            ),
            [
                'event' => [
                    'token' => $token,
                    'siteKey' => config('services.recaptcha.site_key'),
                    'expectedAction' => $this->expectedAction,
                ],
            ],
        );

        $payload = $response->json();
        if (! is_array($payload)) {
            return false;
        }

        $tokenProperties = $payload['tokenProperties'] ?? null;
        if (! is_array($tokenProperties) || ($tokenProperties['valid'] ?? false) !== true) {
            return false;
        }

        if ($this->expectedAction !== null
            && strcasecmp((string) ($tokenProperties['action'] ?? ''), $this->expectedAction) !== 0
        ) {
            return false;
        }

        $score = (float) ($payload['riskAnalysis']['score'] ?? 0);
        $minScore = (float) config('services.recaptcha.min_score', 0.5);

        return $score >= $minScore;
    }

    private function validateLegacySiteVerify(string $token): bool
    {
        $response = Http::asForm()->post(
            'https://www.google.com/recaptcha/api/siteverify',
            [
                'secret' => config('services.recaptcha.secret_key'),
                'response' => $token,
            ]
        );

        $payload = json_decode($response->body(), true);
        if (! is_array($payload) || ($payload['success'] ?? false) !== true) {
            return false;
        }

        if ($this->expectedAction !== null && isset($payload['action'])
            && strcasecmp((string) $payload['action'], $this->expectedAction) !== 0
        ) {
            return false;
        }

        if (isset($payload['score'])) {
            $minScore = (float) config('services.recaptcha.min_score', 0.5);

            return (float) $payload['score'] >= $minScore;
        }

        return true;
    }
}
