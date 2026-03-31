<?php

namespace Cesa\Rekrutmen\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class RecaptchaVerificationService
{
    /**
     * Verifies a reCAPTCHA token with the Google API.
     */
    public function verify(
        string $token,
        string $secretKey,
        ?string $expectedAction = null,
        float $scoreThreshold = 0.0,
        int $timeout = 5,
        ?string $ipAddress = null
    ): bool {
        if (! $token) {
            return false;
        }

        try {
            $response = Http::asForm()
                ->timeout($timeout)
                ->post('https://www.google.com/recaptcha/api/siteverify', [
                    'secret'   => $secretKey,
                    'response' => $token,
                    'remoteip' => $ipAddress,
                ]);

            if (! $response->successful()) {
                Log::warning('reCAPTCHA verification request failed.', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);

                return false;
            }

            $payload = $response->json();

            if (! Arr::get($payload, 'success')) {
                Log::info('reCAPTCHA verification rejected.', [
                    'errors' => Arr::get($payload, 'error-codes'),
                ]);

                return false;
            }

            $score = (float) Arr::get($payload, 'score', 1.0);

            if (
                $scoreThreshold > 0
                && Arr::has($payload, 'score')
                && $score < $scoreThreshold
            ) {
                Log::info('reCAPTCHA score below configured threshold.', [
                    'score'     => $score,
                    'threshold' => $scoreThreshold,
                ]);

                return false;
            }

            $action = Arr::get($payload, 'action');

            if ($action && $expectedAction && $action !== $expectedAction) {
                Log::info('reCAPTCHA action mismatch detected.', [
                    'expected' => $expectedAction,
                    'received' => $action,
                ]);

                return false;
            }
        } catch (Throwable $exception) {
            Log::warning('reCAPTCHA verification failed with exception.', [
                'error' => $exception->getMessage(),
            ]);

            return false;
        }

        return true;
    }
}
