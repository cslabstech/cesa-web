<?php

namespace Cesa\Rekrutmen\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendWhatsAppNotification implements ShouldQueue
{
    use Queueable;

    public int $tries;

    protected int $timeout;

    /**
     * @var array<int, int>
     */
    protected array $backoff;

    public function __construct(
        protected string $phone,
        protected string $message,
        protected string $endpoint,
        protected string $apiKey,
        ?int $timeout = null,
    ) {
        $queue = config('rekrutmen.notifications.whatsapp.queue')
            ?? config('rekrutmen.notifications.queue')
            ?? 'whatsapp';

        $this->onQueue($queue);

        if ($connection = config('rekrutmen.notifications.whatsapp.connection')) {
            $this->onConnection($connection);
        }

        $this->tries = (int) (config('rekrutmen.notifications.whatsapp.tries') ?? 3);
        $this->timeout = $timeout ?? (int) (config('rekrutmen.notifications.whatsapp.timeout') ?? 10);
        $this->backoff = $this->resolveBackoff();
    }

    public function handle(): void
    {
        try {
            $idempotencyKey = 'rekrutmen-' . ($this->job ? $this->job->getJobId() : (string) str()->uuid());

            $response = Http::timeout($this->timeout)
                ->acceptJson()
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Idempotency-Key' => $idempotencyKey,
                ])
                ->post(rtrim($this->endpoint, '/') . '/api/v1/messages', [
                    'recipient' => [
                        'type' => 'phone',
                        'value' => $this->phone,
                    ],
                    'message' => [
                        'type' => 'text',
                        'text' => $this->message,
                    ],
                    'purpose' => 'notification',
                    'mode' => 'async',
                    'route_key' => 'default',
                    'client_reference' => 'rekrutmen',
                ]);

            $response->throw();
        } catch (Throwable $exception) {
            Log::error('Failed to send WhatsApp notification for recruitment approval.', [
                'provider' => 'waghub',
                'phone'    => $this->phone,
                'error'    => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return $this->backoff;
    }

    /**
     * @return array<int, string>
     */
    public function tags(): array
    {
        return [
            'rekrutmen',
            'whatsapp',
            'request-man-power-approval',
        ];
    }

    /**
     * @return array<int, int>
     */
    protected function resolveBackoff(): array
    {
        $backoff = config('rekrutmen.notifications.whatsapp.backoff');

        if (is_array($backoff) && ! empty($backoff)) {
            return array_map(static fn ($interval): int => (int) $interval, $backoff);
        }

        return [10, 30, 60];
    }

}
