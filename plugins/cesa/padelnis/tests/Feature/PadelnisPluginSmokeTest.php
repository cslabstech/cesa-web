<?php

namespace Cesa\Padelnis\Tests\Feature;

use Cesa\Padelnis\Filament\Exports\ReservationExporter;
use Cesa\Padelnis\Filament\Resources\ReservationResource;
use Cesa\Padelnis\Livewire\PublicReservationForm;
use Cesa\Padelnis\Livewire\PublicReservationSuccessPage;
use Cesa\Padelnis\Models\Reservation;
use Cesa\Padelnis\Models\ReservationSlot;
use Cesa\Padelnis\PadelnisPlugin;
use Cesa\Padelnis\PadelnisServiceProvider;
use Cesa\Padelnis\Policies\ReservationPolicy;
use Cesa\Padelnis\Tests\PadelnisTestCase;
use Illuminate\Support\Str;
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
            ReservationSlot::class,
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
            '2026_05_14_000002_create_padelnis_reservation_slots_table',
            '2026_05_15_010300_add_creator_id_to_padelnis_tables',
            '2026_05_16_000702_add_transfer_date_and_notes_to_padelnis_reservations_table',
        ], $package->migrationFileNames);
    }

    public function test_service_provider_keeps_padelnis_in_plugin_extra_tab(): void
    {
        $package = new Package;

        (new PadelnisServiceProvider($this->app))->configureCustomPackage($package);

        $this->assertNull($package->icon);
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

    public function test_reservation_resource_keeps_transfer_amount_thousand_separator_mask(): void
    {
        $resourceSource = file_get_contents(base_path('plugins/cesa/padelnis/src/Filament/Resources/ReservationResource.php'));

        $this->assertIsString($resourceSource);
        $this->assertStringContainsString('TextInput::make(\'transfer_amount\')', $resourceSource);
        $this->assertStringContainsString('extraAlpineAttributes', $resourceSource);
        $this->assertStringContainsString('replace(/,\d{0,2}$/, \'\')', $resourceSource);
        $this->assertStringContainsString('replace(/\\B(?=(\\d{3})+(?!\\d))/g, \'.\')', $resourceSource);
    }

    public function test_reservation_resource_exposes_transfer_date_and_notes_fields(): void
    {
        $resourceSource = file_get_contents(base_path('plugins/cesa/padelnis/src/Filament/Resources/ReservationResource.php'));

        $this->assertIsString($resourceSource);
        $this->assertStringContainsString("DatePicker::make('transfer_date')", $resourceSource);
        $this->assertStringContainsString("Textarea::make('notes')", $resourceSource);
        $this->assertStringContainsString("TextEntry::make('transfer_date')", $resourceSource);
        $this->assertStringContainsString("TextEntry::make('notes')", $resourceSource);
        $this->assertStringNotContainsString('->required()', Str::between(
            $resourceSource,
            "DatePicker::make('transfer_date')",
            "Textarea::make('notes')",
        ));
    }

    public function test_reservation_exporter_defines_reservation_columns(): void
    {
        $this->assertCount(8, ReservationExporter::getColumns());
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

    public function test_reservation_exporter_includes_blocked_slot_details(): void
    {
        $blockedSlotsColumn = collect(ReservationExporter::getColumns())
            ->first(fn ($column): bool => $column->getName() === 'blocked_slots');

        $this->assertNotNull($blockedSlotsColumn);
    }

    public function test_reservation_table_hides_blocked_slot_details_by_default(): void
    {
        $resourceSource = file_get_contents(base_path('plugins/cesa/padelnis/src/Filament/Resources/ReservationResource.php'));

        $this->assertIsString($resourceSource);
        $this->assertStringContainsString("TextColumn::make('blocked_slots')", $resourceSource);
        $this->assertStringContainsString('->toggleable(isToggledHiddenByDefault: true)', $resourceSource);
    }
}
