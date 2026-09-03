<?php

namespace Cesa\Rekrutmen\Tests\Feature;

use Cesa\Rekrutmen\Enums\WhatsAppAccountStatus;
use Cesa\Rekrutmen\Services\WhatsAppGateway;
use Cesa\Rekrutmen\Tests\RekrutmenTestCase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;

class WhatsAppGatewayHardeningTest extends RekrutmenTestCase
{
    public function test_transient_send_failure_does_not_mark_account_disconnected(): void
    {
        config([
            'rekrutmen.notifications.whatsapp.engine_url' => 'http://127.0.0.1:3318',
            'rekrutmen.notifications.whatsapp.auto_start' => false,
            'rekrutmen.notifications.whatsapp.enabled'    => true,
        ]);

        $account = $this->makeConnectedWhatsAppAccount();
        $attempts = 0;

        Http::fake(function (HttpRequest $request) use (&$attempts) {
            if (str_contains($request->url(), '/health')) {
                return Http::response(['ok' => true], 200);
            }

            if (str_contains($request->url(), '/send')) {
                $attempts++;

                if ($attempts === 1) {
                    return Http::response(['ok' => false, 'message' => 'cURL error 28: Operation timed out'], 500);
                }

                return Http::response(['ok' => true, 'status' => 'sent'], 200);
            }

            return Http::response(['ok' => true, 'status' => 'connected'], 200);
        });

        $result = app(WhatsAppGateway::class)->sendText($account, '6281299990000', 'Tes hardening');

        $this->assertTrue($result['success']);
        $this->assertSame(WhatsAppAccountStatus::Connected, $account->fresh()?->status);
        $this->assertSame(2, $attempts);
    }

    public function test_dead_session_send_marks_account_disconnected_after_retry(): void
    {
        config([
            'rekrutmen.notifications.whatsapp.engine_url' => 'http://127.0.0.1:3318',
            'rekrutmen.notifications.whatsapp.auto_start' => false,
            'rekrutmen.notifications.whatsapp.enabled'    => true,
        ]);

        $account = $this->makeConnectedWhatsAppAccount();

        Http::fake(function (HttpRequest $request) {
            if (str_contains($request->url(), '/health')) {
                return Http::response(['ok' => true], 200);
            }

            if (str_contains($request->url(), '/send')) {
                return Http::response([
                    'ok'      => false,
                    'message' => 'Nomor WhatsApp belum terhubung. Scan QR atau minta kode pairing di pengaturan rekrutmen.',
                ], 409);
            }

            return Http::response(['ok' => true, 'status' => 'disconnected'], 200);
        });

        $result = app(WhatsAppGateway::class)->sendText($account, '6281299990000', 'Tes hardening');

        $this->assertFalse($result['success']);
        $this->assertSame(WhatsAppAccountStatus::Disconnected, $account->fresh()?->status);
    }

    public function test_brief_disconnected_engine_status_does_not_drop_connected_account(): void
    {
        $this->fakeRekrutmenWhatsAppEngine([
            'status' => 'disconnected',
            'error'  => null,
            'phone'  => null,
        ]);

        $account = $this->makeConnectedWhatsAppAccount([
            'phone_number' => '6287815742597',
        ]);

        $payload = app(WhatsAppGateway::class)->session($account);

        $this->assertSame(WhatsAppAccountStatus::Connected, $account->fresh()?->status);
        $this->assertSame('6287815742597', $account->fresh()?->phone_number);
        $this->assertSame('connected', $payload['status']);
    }
}
