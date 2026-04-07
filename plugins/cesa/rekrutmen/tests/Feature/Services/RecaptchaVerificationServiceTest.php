<?php

namespace Cesa\Rekrutmen\Tests\Feature\Services;

use Cesa\Rekrutmen\Services\RecaptchaVerificationService;
use Cesa\Rekrutmen\Tests\RekrutmenTestCase;
use Illuminate\Support\Facades\Http;

class RecaptchaVerificationServiceTest extends RekrutmenTestCase
{
    public function test_verify_rejects_hostname_mismatch(): void
    {
        Http::fake([
            'https://www.google.com/recaptcha/api/siteverify' => Http::response([
                'success'  => true,
                'score'    => 0.9,
                'action'   => 'request_man_power',
                'hostname' => 'unexpected.test',
            ]),
        ]);

        $service = new RecaptchaVerificationService;

        $this->assertFalse($service->verify(
            'token',
            'secret',
            'request_man_power',
            'web-cesa.test',
            0.5,
            5,
            '127.0.0.1',
        ));
    }

    public function test_verify_accepts_matching_hostname(): void
    {
        Http::fake([
            'https://www.google.com/recaptcha/api/siteverify' => Http::response([
                'success'  => true,
                'score'    => 0.9,
                'action'   => 'request_man_power',
                'hostname' => 'web-cesa.test',
            ]),
        ]);

        $service = new RecaptchaVerificationService;

        $this->assertTrue($service->verify(
            'token',
            'secret',
            'request_man_power',
            'web-cesa.test',
            0.5,
            5,
            '127.0.0.1',
        ));
    }
}
