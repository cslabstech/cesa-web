<?php

namespace Cesa\FormTransfer\Livewire;

use Cesa\FormTransfer\Models\FormTransferPublicCategory;
use Filament\Pages\SimplePage;
use Illuminate\Database\Eloquent\Collection;
use Webkul\PluginManager\Package;

class PublicCategoryIndex extends SimplePage
{
    protected static string $layout = 'form-transfer::layouts.form';

    protected string $view = 'form-transfer::livewire.public-category-index';

    public function mount(): void
    {
        if (! Package::isPluginInstalled('form-transfer')) {
            abort(404);
        }
    }

    public function getHeading(): string
    {
        return __('form-transfer::public.categories.heading');
    }

    public function getSubheading(): string
    {
        return __('form-transfer::public.categories.description');
    }

    public function hasLogo(): bool
    {
        return false;
    }

    protected function getViewData(): array
    {
        return [
            'categories'         => $this->getActiveCategories(),
            'heading'            => $this->getHeading(),
            'description'        => $this->getSubheading(),
            'defaultDescription' => __('form-transfer::public.categories.default_description'),
            'emptyState'         => __('form-transfer::public.categories.empty_state'),
        ];
    }

    protected function getActiveCategories(): Collection
    {
        return FormTransferPublicCategory::query()
            ->active()
            ->ordered()
            ->get();
    }
}
