<?php

namespace Cesa\FormTransfer\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendWhatsAppNotification implements ShouldQueue
{
    use Queueable;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries;

    /**
     * The timeout in seconds for the WhatsApp HTTP request.
     */
    protected int $timeout;

    /**
     * The backoff intervals in seconds between retries.
     *
     * @var array<int, int>
     */
    protected array $backoff;

    public function __construct(
        protected string $phone,
        protected string $message,
        protected string $endpoint,
        protected string $apiKey,
        protected string $sender,
        ?int $timeout = null,
    ) {
        $queue = config('form-transfer.notifications.whatsapp.queue')
            ?? config('form-transfer.notifications.queue')
            ?? 'whatsapp';

        $this->onQueue($queue);

        if ($connection = config('form-transfer.notifications.whatsapp.connection')) {
            $this->onConnection($connection);
        }

        $this->tries = (int) (config('form-transfer.notifications.whatsapp.tries') ?? 3);
        $this->timeout = $timeout ?? (int) (config('form-transfer.notifications.whatsapp.timeout') ?? 10);
        $this->backoff = $this->resolveBackoff();
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $provider = strtolower(trim((string) config('form-transfer.notifications.whatsapp.provider', 'generic')));

        try {
            if ($provider === 'fonnte') {
                $countryCode = (string) config('form-transfer.notifications.whatsapp.country_code', '62');
                $target = $this->buildFonnteTarget($this->phone, $countryCode);

                $response = Http::timeout($this->timeout)
                    ->acceptJson()
                    ->withHeaders([
                        'Authorization' => $this->apiKey,
                    ])
                    ->asMultipart()
                    ->post($this->endpoint, [
                        'target'      => $target,
                        'message'     => $this->message,
                        'countryCode' => $countryCode,
                    ]);

                $response->throw();

                $payload = $response->json();

                if (($status = $this->resolveFonnteStatus($payload)) === false) {
                    $reason = $this->resolveFonnteReason($payload);
                    throw new \RuntimeException((string) $reason);
                }

                return;
            }

            Http::timeout($this->timeout)
                ->acceptJson()
                ->get($this->endpoint, [
                    'apikey'   => $this->apiKey,
                    'sender'   => $this->sender,
                    'receiver' => $this->phone,
                    'message'  => $this->message,
                ])
                ->throw();
        } catch (Throwable $exception) {
            Log::error('Failed to send WhatsApp notification for transfer approval.', [
                'provider' => $provider,
                'phone'    => $this->phone,
                'error'    => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    /**
     * Determine the backoff intervals for the job retry attempts.
     *
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return $this->backoff;
    }

    /**
     * Define tags for queue monitoring systems like Horizon.
     *
     * @return array<int, string>
     */
    public function tags(): array
    {
        return [
            'form-transfer',
            'whatsapp',
            'transfer-request',
        ];
    }

    /**
     * Resolve the backoff configuration.
     *
     * @return array<int, int>
     */
    protected function resolveBackoff(): array
    {
        $backoff = config('form-transfer.notifications.whatsapp.backoff');

        if (is_array($backoff) && ! empty($backoff)) {
            return array_map(static fn ($interval): int => (int) $interval, $backoff);
        }

        return [10, 30, 60];
    }

    protected function buildFonnteTarget(string $phone, string $countryCode): string
    {
        $target = preg_replace('/[^\d]/', '', $phone);

        if (! is_string($target) || $target === '') {
            return '';
        }

        $normalizedCountryCode = preg_replace('/[^\d]/', '', $countryCode);

        if (! is_string($normalizedCountryCode) || $normalizedCountryCode === '' || $normalizedCountryCode === '0') {
            return $target;
        }

        if (str_starts_with($target, $normalizedCountryCode)) {
            return '0'.substr($target, strlen($normalizedCountryCode));
        }

        if (str_starts_with($target, '0')) {
            return $target;
        }

        if (str_starts_with($target, '8')) {
            return '0'.$target;
        }

        return $target;
    }

    protected function resolveFonnteStatus(mixed $payload): ?bool
    {
        if (! is_array($payload)) {
            return null;
        }

        $normalizedPayload = array_change_key_case($payload, CASE_LOWER);

        if (! array_key_exists('status', $normalizedPayload)) {
            return null;
        }

        return filter_var($normalizedPayload['status'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    }

    protected function resolveFonnteReason(mixed $payload): string
    {
        if (! is_array($payload)) {
            return 'Fonnte rejected the request.';
        }

        $normalizedPayload = array_change_key_case($payload, CASE_LOWER);

        foreach (['reason', 'detail', 'message'] as $key) {
            if (filled($normalizedPayload[$key] ?? null)) {
                return (string) $normalizedPayload[$key];
            }
        }

        return 'Fonnte rejected the request.';
    }
}
