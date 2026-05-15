<?php

namespace Cesa\Lead;

use Cesa\DatabaseSnapshot\Services\DatabaseSnapshotManager;
use Cesa\Lead\Livewire\PublicLeadForm;
use Cesa\Lead\Models\Lead;
use Cesa\Lead\Policies\LeadPolicy;
use Filament\Panel;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Webkul\PluginManager\Console\Commands\InstallCommand;
use Webkul\PluginManager\Console\Commands\UninstallCommand;
use Webkul\PluginManager\Package;
use Webkul\PluginManager\PackageServiceProvider;

class LeadServiceProvider extends PackageServiceProvider
{
    public static string $name = 'lead';

    public function configureCustomPackage(Package $package): void
    {
        $package->name(static::$name)
            ->hasConfigFile()
            ->hasTranslations()
            ->hasViews()
            ->hasRoute('web')
            ->hasMigrations([
                '2025_10_30_140000_create_leads_table',
                '2026_03_26_141500_add_public_response_id_to_leads_table',
                '2026_05_15_000000_drop_public_response_id_from_leads_table',
                '2026_05_15_011000_rename_legacy_creator_column_in_leads_table',
            ])
            ->runsMigrations()
            ->hasInstallCommand(function (InstallCommand $command): void {
                $command->runsMigrations();
            })
            ->hasUninstallCommand(function (UninstallCommand $command): void {});
    }

    public function packageRegistered(): void
    {
        Panel::configureUsing(function (Panel $panel): void {
            $panel->plugin(LeadPlugin::make());
        });
    }

    public function packageBooted(): void
    {
        if (! ($this->package->isCore || $this->package->isInstalled())) {
            return;
        }

        Livewire::component('cesa.lead.livewire.public-lead-form', PublicLeadForm::class);

        Gate::policy(Lead::class, LeadPolicy::class);

        // Register snapshot metadata for database backup/restore
        $this->registerSnapshotMetadata();
    }

    protected function registerSnapshotMetadata(): void
    {
        if (! app()->bound(DatabaseSnapshotManager::class)) {
            return;
        }

        $manager = app(DatabaseSnapshotManager::class);

        $manager->registerPlugin('lead', [
            'version' => '1.0.0',
            'tables'  => [
                'leads',
            ],
        ]);
    }
}
