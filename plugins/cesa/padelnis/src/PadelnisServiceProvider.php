<?php

namespace Cesa\Padelnis;

use Cesa\DatabaseSnapshot\Services\DatabaseSnapshotManager;
use Cesa\Padelnis\Livewire\PublicReservationForm;
use Cesa\Padelnis\Livewire\PublicReservationSuccessPage;
use Cesa\Padelnis\Models\Reservation;
use Cesa\Padelnis\Policies\ReservationPolicy;
use Cesa\Padelnis\Services\ReservationReferenceService;
use Filament\Panel;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Webkul\PluginManager\Console\Commands\InstallCommand;
use Webkul\PluginManager\Console\Commands\UninstallCommand;
use Webkul\PluginManager\Package;
use Webkul\PluginManager\PackageServiceProvider;

class PadelnisServiceProvider extends PackageServiceProvider
{
    public static string $name = 'padelnis';

    public function configureCustomPackage(Package $package): void
    {
        $package->name(static::$name)
            ->hasConfigFile()
            ->hasTranslations()
            ->hasViews()
            ->hasRoute('web')
            ->hasMigrations([
                '2026_05_14_000000_create_padelnis_reservations_table',
                '2026_05_14_000001_add_active_slot_key_to_padelnis_reservations_table',
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
            $panel->plugin(PadelnisPlugin::make());
        });

        $this->app->singleton(ReservationReferenceService::class);
    }

    public function packageBooted(): void
    {
        if (! ($this->package->isCore || $this->package->isInstalled())) {
            return;
        }

        Livewire::component('cesa.padelnis.livewire.public-reservation-form', PublicReservationForm::class);
        Livewire::component('cesa.padelnis.livewire.public-reservation-success-page', PublicReservationSuccessPage::class);

        Gate::policy(Reservation::class, ReservationPolicy::class);

        $this->registerSnapshotMetadata();
    }

    protected function registerSnapshotMetadata(): void
    {
        if (! app()->bound(DatabaseSnapshotManager::class)) {
            return;
        }

        app(DatabaseSnapshotManager::class)->registerPlugin('padelnis', [
            'version' => '1.0.0',
            'tables'  => [
                'padelnis_reservations',
            ],
        ]);
    }
}
