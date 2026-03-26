<?php

namespace Cesa\Rekrutmen\Tests\Feature;

use Cesa\Rekrutmen\Tests\RekrutmenTestCase;

class PublicRequestManPowerFormTest extends RekrutmenTestCase
{
    public function test_public_request_man_power_form_uses_plain_textareas_for_long_text_fields(): void
    {
        app()->setLocale('en');

        $response = $this->get('/man-power');

        $response
            ->assertOk()
            ->assertSee(e(__('rekrutmen::livewire/public-request-man-power-form.sections.applicant_information')), false)
            ->assertSee(e(__('rekrutmen::livewire/public-request-man-power-form.sections.position_requirements')), false)
            ->assertSee(e(__('rekrutmen::livewire/public-request-man-power-form.sections.qualifications_and_description')), false)
            ->assertSee(e(__('rekrutmen::livewire/public-request-man-power-form.sections.requirement_status')), false)
            ->assertSee(__('rekrutmen::livewire/public-request-man-power-form.fields.requirements_kualifikasi'), false)
            ->assertSee(__('rekrutmen::livewire/public-request-man-power-form.fields.job_description'), false)
            ->assertDontSee('fi-fo-rich-editor', false);
    }
}
