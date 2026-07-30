<?php

namespace Cesa\Rekrutmen\Services;

use Cesa\Rekrutmen\Jobs\SendWhatsAppNotification;
use Cesa\Rekrutmen\Models\RequestManPower;
use Cesa\Rekrutmen\Models\RequestManPowerApproval;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class RequestManPowerApprovalWhatsAppNotifier
{
    public function send(RequestManPower $requestManPower, RequestManPowerApproval $approval): void
    {
        $approval->loadMissing('approver');

        $phone = $approval->approver?->phone;

        if (! filled($phone)) {
            return;
        }

        $config = config('rekrutmen.notifications.whatsapp', []);

        if (! Arr::get($config, 'enabled')) {
            return;
        }

        $endpoint = Arr::get($config, 'endpoint');
        $apiKey = Arr::get($config, 'api_key');
        if (! $endpoint || ! $apiKey) {
            Log::warning('Recruitment WhatsApp approval notification skipped due to missing configuration.', [
                'endpoint' => $endpoint,
                'api_key'  => $apiKey ? 'configured' : 'missing',
            ]);

            return;
        }

        $formattedPhone = $this->formatPhone((string) $phone);

        if (! $formattedPhone) {
            Log::warning('Recruitment WhatsApp approval notification skipped due to invalid phone.', [
                'approval_id' => $approval->getKey(),
                'phone'       => $phone,
            ]);

            return;
        }

        $message = $this->buildApprovalRequestMessage($requestManPower, $approval);
        $timeout = (int) ($config['timeout'] ?? 10);
        $delaySeconds = app(WhatsAppThrottleService::class)->getDispatchDelaySeconds();

        $pendingDispatch = SendWhatsAppNotification::dispatch(
            $formattedPhone,
            $message,
            $endpoint,
            $apiKey,
            $timeout,
        );

        if ($delaySeconds > 0) {
            $pendingDispatch->delay($delaySeconds);
        }
    }

    protected function buildApprovalRequestMessage(RequestManPower $requestManPower, RequestManPowerApproval $approval): string
    {
        $summaryFields = __('rekrutmen::mail/request-man-power-approval-request.summary_fields');

        return implode("\n", [
            '*📣 PERMINTAAN TENAGA KERJA BARU*',
            '',
            '*'.$summaryFields['submission_date'].':* '.$requestManPower->getTanggalPengajuanFormattedAttribute(),
            '*'.$summaryFields['applicant'].':* '.($requestManPower->nama_pengaju ?? '-'),
            '*'.$summaryFields['position'].':* '.($requestManPower->posisi_dibutuhkan ?? '-'),
            '*'.$summaryFields['requirement'].':* '.($requestManPower->status_kebutuhan?->getLabel() ?? '-'),
            '*'.$summaryFields['division'].':* '.($requestManPower->division_name ?? '-'),
            '*'.$summaryFields['business_entity'].':* '.($requestManPower->business_entity_name ?? '-'),
            '*'.$summaryFields['estimated_join'].':* '.$requestManPower->getEstimasiTanggalJoinFormattedAttribute(),
            '',
            '*Tautan persetujuan:*',
            $approval->buildApprovalUrl(),
        ]);
    }

    protected function formatPhone(string $phone): ?string
    {
        $trimmed = trim($phone);

        if ($trimmed === '') {
            return null;
        }

        $digitsOnly = preg_replace('/[^\d]/', '', $trimmed);

        if (! is_string($digitsOnly) || $digitsOnly === '') {
            return null;
        }

        if (str_starts_with($digitsOnly, '62')) {
            return $digitsOnly;
        }

        if (str_starts_with($digitsOnly, '0')) {
            return '62'.substr($digitsOnly, 1);
        }

        if (str_starts_with($digitsOnly, '8')) {
            return '62'.$digitsOnly;
        }

        return $digitsOnly;
    }
}
