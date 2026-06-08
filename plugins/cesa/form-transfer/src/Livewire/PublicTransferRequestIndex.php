<?php

namespace Cesa\FormTransfer\Livewire;

use Cesa\FormTransfer\Models\FormTransfer;
use Filament\Pages\SimplePage;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use Webkul\PluginManager\Package;

class PublicTransferRequestIndex extends SimplePage
{
    protected static string $layout = 'form-transfer::layouts.form';

    protected string $view = 'form-transfer::livewire.public-transfer-request-index';

    public bool $affiliateMode = false;

    public string $publicIndexSlug = FormTransfer::PUBLIC_INDEX_TRANSFER_REQUESTS;

    public function mount(): void
    {
        if (! Package::isPluginInstalled('form-transfer')) {
            abort(404);
        }

        $this->publicIndexSlug = $this->resolvePublicIndexSlug();
        $this->affiliateMode = $this->publicIndexSlug === FormTransfer::PUBLIC_INDEX_AFFILIATES;

        if (
            ! FormTransfer::isBuiltInPublicIndexSlug($this->publicIndexSlug)
            && ! FormTransfer::hasPublicIndexSlug($this->publicIndexSlug)
        ) {
            abort(404);
        }
    }

    public function getHeading(): string
    {
        return match ($this->publicIndexSlug) {
            FormTransfer::PUBLIC_INDEX_AFFILIATES        => __('form-transfer::public.affiliates.heading'),
            FormTransfer::PUBLIC_INDEX_TRANSFER_REQUESTS => __('form-transfer::public.index.heading'),
            default                                      => __('form-transfer::public.catalog.heading', [
                'category' => Str::of($this->publicIndexLabel())->upper()->toString(),
            ]),
        };
    }

    public function getSubheading(): string
    {
        return match ($this->publicIndexSlug) {
            FormTransfer::PUBLIC_INDEX_AFFILIATES        => __('form-transfer::public.affiliates.description'),
            FormTransfer::PUBLIC_INDEX_TRANSFER_REQUESTS => __('form-transfer::public.index.description'),
            default                                      => __('form-transfer::public.catalog.description', [
                'category' => $this->publicIndexLabel(),
            ]),
        };
    }

    public function hasLogo(): bool
    {
        return false;
    }

    protected function getViewData(): array
    {
        return [
            'formTransfers'      => $this->getActiveFormTransfers(),
            'affiliateMode'      => $this->affiliateMode,
            'publicIndexSlug'    => $this->publicIndexSlug,
            'publicIndexLabel'   => $this->publicIndexLabel(),
            'heading'            => $this->getHeading(),
            'description'        => $this->getSubheading(),
            'defaultDescription' => $this->defaultDescription(),
            'emptyState'         => $this->emptyState(),
        ];
    }

    protected function getActiveFormTransfers(): Collection
    {
        return FormTransfer::query()
            ->with('publicCategories')
            ->where('is_active', true)
            ->shownOnPublicIndex($this->publicIndexSlug)
            ->orderBy('public_sort_order')
            ->orderBy('name')
            ->get();
    }

    protected function resolvePublicIndexSlug(): string
    {
        $route = request()->route();

        if ((bool) ($route?->named('form-transfer.public.dynamic-index') ?? false)) {
            $slug = FormTransfer::normalizePublicIndexSlug($route?->parameter('publicIndexSlug'));

            if (! FormTransfer::isAllowedPublicIndexSlug($slug)) {
                abort(404);
            }

            return $slug;
        }

        abort(404);
    }

    protected function publicIndexLabel(): string
    {
        return Str::of($this->publicIndexSlug)
            ->replace('-', ' ')
            ->headline()
            ->toString();
    }

    protected function defaultDescription(): string
    {
        return match ($this->publicIndexSlug) {
            FormTransfer::PUBLIC_INDEX_AFFILIATES        => __('form-transfer::public.affiliates.default_description'),
            FormTransfer::PUBLIC_INDEX_TRANSFER_REQUESTS => __('form-transfer::public.index.default_description'),
            default                                      => __('form-transfer::public.catalog.default_description', [
                'category' => $this->publicIndexLabel(),
            ]),
        };
    }

    protected function emptyState(): string
    {
        return match ($this->publicIndexSlug) {
            FormTransfer::PUBLIC_INDEX_AFFILIATES        => __('form-transfer::public.affiliates.empty_state'),
            FormTransfer::PUBLIC_INDEX_TRANSFER_REQUESTS => __('form-transfer::public.index.empty_state'),
            default                                      => __('form-transfer::public.catalog.empty_state', [
                'category' => $this->publicIndexLabel(),
            ]),
        };
    }
}
