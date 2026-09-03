<?php

namespace Cesa\Rekrutmen\Jobs;

use Cesa\Rekrutmen\Models\WhatsAppAccount;
use Cesa\Rekrutmen\Services\WhatsAppGateway;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use RuntimeException;
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
        protected int $accountId,
        protected string $phone,
        protected string $message,
    ) {
        $queue = config('rekrutmen.notifications.whatsapp.queue')
            ?? config('rekrutmen.notifications.queue')
            ?? 'whatsapp';

        $this->onQueue($queue);

        if ($connection = config('rekrutmen.notifications.whatsapp.connection')) {
            $this->onConnection($connection);
        }

        $this->tries = (int) (config('rekrutmen.notifications.whatsapp.tries') ?? 3);
        $this->timeout = (int) (config('rekrutmen.notifications.whatsapp.timeout') ?? 20);
        $this->backoff = $this->resolveBackoff();
    }

    public function handle(WhatsAppGateway $gateway): void
    {
        try {
            $account = WhatsAppAccount::query()->find($this->accountId);
            $result = $gateway->sendText($account, $this->phone, $this->message, [
                'mode'             => 'async',
                'client_reference' => 'rekrutmen',
            ]);

            if (! ($result['success'] ?? false)) {
                throw new RuntimeException($result['message'] ?? 'Gagal mengirim WhatsApp rekrutmen.');
            }
        } catch (Throwable $exception) {
            Log::error('Failed to send WhatsApp notification for recruitment approval.', [
                'provider'   => 'rekrutmen-engine',
                'account_id' => $this->accountId,
                'phone'      => $this->phone,
                'error'      => $exception->getMessage(),
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
