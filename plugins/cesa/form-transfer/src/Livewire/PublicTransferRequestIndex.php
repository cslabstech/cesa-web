<?php

namespace Cesa\FormTransfer\Livewire;

use Cesa\FormTransfer\Models\FormTransfer;
use Filament\Pages\SimplePage;
use Webkul\PluginManager\Package;

class PublicTransferRequestIndex extends SimplePage
{
    protected static string $layout = 'form-transfer::layouts.form';

    protected string $view = 'form-transfer::livewire.public-transfer-request-index';

    public bool $affiliateMode = false;

    public function mount(): void
    {
        if (! Package::isPluginInstalled('form-transfer')) {
            abort(404);
        }

        $route = request()->route();

        $this->affiliateMode = (bool) ($route?->named('form-transfer.public.affiliates') ?? false)
            || request()->is('afiliasi');
    }

    public function getHeading(): string
    {
        return $this->affiliateMode
            ? __('form-transfer::public.affiliates.heading')
            : __('form-transfer::public.index.heading');
    }

    public function getSubheading(): string
    {
        return $this->affiliateMode
            ? __('form-transfer::public.affiliates.description')
            : __('form-transfer::public.index.description');
    }

    public function hasLogo(): bool
    {
        return false;
    }

    protected function getViewData(): array
    {
        return [
            'formTransfers' => $this->getActiveFormTransfers(),
            'affiliateMode' => $this->affiliateMode,
        ];
    }

    protected function getActiveFormTransfers()
    {
        return FormTransfer::query()
            ->where('is_active', true)
            ->where(
                $this->affiliateMode
                    ? 'show_on_affiliate_index'
                    : 'show_on_transfer_request_index',
                true
            )
            ->orderBy('public_sort_order')
            ->orderBy('name')
            ->get();
    }
}
