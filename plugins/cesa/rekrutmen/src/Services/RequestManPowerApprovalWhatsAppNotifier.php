<?php

namespace Cesa\Rekrutmen\Services;

use Cesa\Rekrutmen\Jobs\SendWhatsAppNotification;
use Cesa\Rekrutmen\Models\RequestManPower;
use Cesa\Rekrutmen\Models\RequestManPowerApproval;
use Cesa\Rekrutmen\Models\WhatsAppAccount;
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

        $gateway = app(WhatsAppGateway::class);

        if (! $gateway->isEnabled()) {
            return;
        }

        $account = WhatsAppAccount::resolveForSend();

        if (! $account) {
            Log::warning('Recruitment WhatsApp approval notification skipped because no connected number exists.');

            return;
        }

        $formattedPhone = $gateway->formatPhone((string) $phone);

        if (! $formattedPhone) {
            Log::warning('Recruitment WhatsApp approval notification skipped due to invalid phone.', [
                'approval_id' => $approval->getKey(),
                'phone'       => $phone,
            ]);

            return;
        }

        $message = $this->buildApprovalRequestMessage($requestManPower, $approval);
        $delaySeconds = app(WhatsAppThrottleService::class)->getDispatchDelaySeconds();

        $pendingDispatch = SendWhatsAppNotification::dispatch(
            (int) $account->getKey(),
            $formattedPhone,
            $message,
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
        return app(WhatsAppGateway::class)->formatPhone($phone);
    }
}
