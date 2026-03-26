<?php

namespace Cesa\Lead\Livewire;

use Cesa\Lead\Models\Lead;
use Filament\Pages\SimplePage;
use Webkul\PluginManager\Package;

class PublicLeadProgressPage extends SimplePage
{
    protected static string $layout = 'lead::layouts.form';

    protected string $view = 'lead::livewire.public-lead-progress-page';

    public Lead $lead;

    public function mount(string $response): void
    {
        if (! Package::isPluginInstalled('lead')) {
            abort(404);
        }

        $this->lead = Lead::query()
            ->where('public_response_id', $response)
            ->firstOrFail();
    }

    public function getHeading(): string
    {
        return __('lead::views/public-lead-form.summary.title');
    }

    public function getSubheading(): string
    {
        return __('lead::views/public-lead-form.summary.description');
    }

    public function hasLogo(): bool
    {
        return false;
    }
}
