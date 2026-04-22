<?php

namespace Cesa\Rekrutmen\Tests\Feature;

use Cesa\Rekrutmen\Enums\StatusKebutuhan;
use Cesa\Rekrutmen\Jobs\SendWhatsAppNotification;
use Cesa\Rekrutmen\Models\RequestManPower;
use Cesa\Rekrutmen\Models\RequestManPowerApproval;
use Cesa\Rekrutmen\Services\RequestManPowerApprovalWhatsAppNotifier;
use Cesa\Rekrutmen\Tests\RekrutmenTestCase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Webkul\Support\Models\Company;

class SendWhatsAppNotificationTest extends RekrutmenTestCase
{
    public function test_rekrutmen_fonnte_job_uses_authorization_header_and_local_target(): void
    {
        config()->set('rekrutmen.notifications.whatsapp.provider', 'fonnte');
        config()->set('rekrutmen.notifications.whatsapp.country_code', '62');

        Http::fake([
            'https://api.fonnte.com/send' => Http::response(['status' => true], 200),
        ]);

        $job = new SendWhatsAppNotification(
            '+628123456789',
            'Test message',
            'https://api.fonnte.com/send',
            'test-token',
            '',
        );

        $job->handle();

        Http::assertSent(function (HttpRequest $request): bool {
            $body = $request->body();

            return $request->url() === 'https://api.fonnte.com/send'
                && $request->hasHeader('Authorization', 'test-token')
                && str_contains($body, 'name="target"')
                && str_contains($body, '08123456789')
                && str_contains($body, 'name="countryCode"')
                && str_contains($body, '62');
        });
    }

    public function test_rekrutmen_fonnte_job_detects_uppercase_status_failures(): void
    {
        config()->set('rekrutmen.notifications.whatsapp.provider', 'fonnte');

        Http::fake([
            'https://api.fonnte.com/send' => Http::response([
                'Status' => false,
                'reason' => 'token invalid',
            ], 200),
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('token invalid');

        $job = new SendWhatsAppNotification(
            '628123456789',
            'Test message',
            'https://api.fonnte.com/send',
            'test-token',
            '',
        );

        $job->handle();
    }

    public function test_rekrutmen_whatsapp_message_uses_professional_consistent_copy_without_progress(): void
    {
        $service = app(RequestManPowerApprovalWhatsAppNotifier::class);
        $company = new Company(['name' => 'PT Cesa Indonesia']);
        $request = new RequestManPower([
            'status_response_id'         => 'RMP-00001',
            'nama_pengaju'               => 'Siti Rahma',
            'posisi_dibutuhkan'          => 'Sales Supervisor',
            'status_kebutuhan'           => StatusKebutuhan::NEW_HIRING,
            'divisi'                     => 'Sales',
            'estimasi_tanggal_join'      => '2026-05-01',
            'tanggal_pengajuan'          => '2026-04-22',
            'email_address'              => 'siti@example.com',
            'jumlah_karyawan_dibutuhkan' => 1,
        ]);
        $request->setRelation('company', $company);

        $approval = new RequestManPowerApproval([
            'approver_name' => 'Manager HR',
            'action_token'  => 'demo-token',
        ]);

        $method = new \ReflectionMethod($service, 'buildApprovalRequestMessage');
        $method->setAccessible(true);
        $message = $method->invoke($service, $request, $approval);

        $this->assertStringContainsString('📣 PERMINTAAN TENAGA KERJA BARU', $message);
        $this->assertStringContainsString('*Tautan persetujuan:*', $message);
        $this->assertStringContainsString("*📣 PERMINTAAN TENAGA KERJA BARU*\n\n*Tanggal Pengajuan:*", $message);
        $this->assertMatchesRegularExpression('/\*Estimasi Join:\* .*?\n\n\*Tautan persetujuan:\*/s', $message);
        $this->assertStringNotContainsString('RMP-00001', $message);
        $this->assertStringNotContainsString('Lihat Progres Pengajuan', $message);
        $this->assertStringNotContainsString('progress', strtolower($message));
        $this->assertStringNotContainsString('Catatan:', $message);
    }
}
