<?php

namespace Cesa\Padelnis\Tests\Feature;

use Cesa\Padelnis\Filament\Exports\ReservationExporter;
use Cesa\Padelnis\Filament\Resources\ReservationResource;
use Cesa\Padelnis\Livewire\PublicReservationForm;
use Cesa\Padelnis\Livewire\PublicReservationSuccessPage;
use Cesa\Padelnis\Models\Reservation;
use Cesa\Padelnis\PadelnisPlugin;
use Cesa\Padelnis\PadelnisServiceProvider;
use Cesa\Padelnis\Policies\ReservationPolicy;
use Cesa\Padelnis\Tests\PadelnisTestCase;
use Webkul\PluginManager\Package;

class PadelnisPluginSmokeTest extends PadelnisTestCase
{
    public function test_it_uses_the_padelnis_identity(): void
    {
        $this->assertSame('padelnis', PadelnisServiceProvider::$name);
        $this->assertSame('padelnis', app(PadelnisPlugin::class)->getId());
    }

    public function test_it_can_autoload_padelnis_entrypoints(): void
    {
        foreach ([
            Reservation::class,
            ReservationExporter::class,
            ReservationResource::class,
            PublicReservationForm::class,
            PublicReservationSuccessPage::class,
            ReservationPolicy::class,
        ] as $class) {
            $this->assertTrue(class_exists($class), "Failed asserting {$class} can be autoloaded.");
        }
    }

    public function test_service_provider_registers_padelnis_migration(): void
    {
        $package = new Package;

        (new PadelnisServiceProvider($this->app))->configureCustomPackage($package);

        $this->assertSame([
            '2026_05_14_000000_create_padelnis_reservations_table',
            '2026_05_14_000001_add_active_slot_key_to_padelnis_reservations_table',
        ], $package->migrationFileNames);
    }

    public function test_reservation_resource_labels_are_localized(): void
    {
        foreach (['en', 'id'] as $locale) {
            app()->setLocale($locale);

            $this->assertSame(trans('padelnis::filament/resources/reservation.navigation.title', [], $locale), ReservationResource::getNavigationLabel());
            $this->assertSame(trans('padelnis::filament/resources/reservation.navigation.group', [], $locale), ReservationResource::getNavigationGroup());
            $this->assertSame(trans('padelnis::filament/resources/reservation.singular', [], $locale), ReservationResource::getModelLabel());
            $this->assertSame(trans('padelnis::filament/resources/reservation.plural', [], $locale), ReservationResource::getPluralModelLabel());
        }
    }

    public function test_reservation_exporter_defines_reservation_columns(): void
    {
        $this->assertCount(7, ReservationExporter::getColumns());
    }

    public function test_reservation_exporter_formats_reservation_time_as_slot_label(): void
    {
        $reservationTimeColumn = collect(ReservationExporter::getColumns())
            ->first(fn ($column): bool => $column->getName() === 'reservation_time');

        $this->assertSame('06:00 - 07:00', $reservationTimeColumn->formatState('06:00'));
        $this->assertSame('06:00 - 07:00', $reservationTimeColumn->formatState('06:00:00'));
        $this->assertSame('06:00 - 07:00', $reservationTimeColumn->formatState('06:00:00 - 07:00:00'));
        $this->assertSame('06:00 - 07:00', $reservationTimeColumn->formatState('06:00 - 07:00'));
    }
}
