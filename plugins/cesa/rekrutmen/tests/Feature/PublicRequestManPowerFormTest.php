<?php

namespace Cesa\Rekrutmen\Tests\Feature;

use Cesa\Rekrutmen\Tests\RekrutmenTestCase;

class PublicRequestManPowerFormTest extends RekrutmenTestCase
{
    public function test_public_request_man_power_form_uses_plain_textareas_for_long_text_fields(): void
    {
        app()->setLocale('en');

        $response = $this->get('/man-power');

        $fieldLabels = [
            __('rekrutmen::livewire/public-request-man-power-form.fields.nama_pengaju'),
            __('rekrutmen::livewire/public-request-man-power-form.fields.posisi_dibutuhkan'),
            __('rekrutmen::livewire/public-request-man-power-form.fields.requirements_kualifikasi'),
            __('rekrutmen::livewire/public-request-man-power-form.fields.job_description'),
        ];

        $response
            ->assertOk()
            ->assertDontSee('fi-fo-rich-editor', false);

        foreach ($fieldLabels as $fieldLabel) {
            $response->assertSee(e($fieldLabel), false);
        }
    }
}
