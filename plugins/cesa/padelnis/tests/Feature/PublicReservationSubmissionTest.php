<?php

namespace Cesa\Padelnis\Tests\Feature;

use Cesa\Padelnis\Livewire\PublicReservationForm;
use Cesa\Padelnis\Models\Reservation;
use Cesa\Padelnis\Services\ReservationReferenceService;
use Cesa\Padelnis\Tests\PadelnisTestCase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

class PublicReservationSubmissionTest extends PadelnisTestCase
{
    public function test_can_render_public_reservation_form_page(): void
    {
        $this->get('/padelnis')
            ->assertOk()
            ->assertSee(__('padelnis::views/public-reservation-form.title'));
    }

    public function test_public_reservation_form_keeps_transfer_amount_thousand_separator_mask(): void
    {
        $this->get('/padelnis')
            ->assertOk()
            ->assertSee('x-on:input', false)
            ->assertSee('replace(/,\\d{0,2}$/, \'\')', false)
            ->assertSee('replace(/\\B(?=(\\d{3})+(?!\\d))/g, \'.\')', false);
    }

    public function test_reservation_model_generates_yearly_reference_id(): void
    {
        $firstReservation = Reservation::factory()->create([
            'reservation_date' => '2026-06-01',
            'court'            => 'Padel Court VIP Blue 1',
            'reservation_time' => '06:00 - 07:00',
        ]);
        $secondReservation = Reservation::factory()->create([
            'reservation_date' => '2026-06-01',
            'court'            => 'Padel Court VIP Blue 2',
            'reservation_time' => '06:00 - 07:00',
        ]);

        $year = now()->format('Y');

        $this->assertSame('UID0001', $firstReservation->id_reff);
        $this->assertSame('UID0002', $secondReservation->id_reff);
    }

    public function test_reservation_model_retries_duplicate_generated_reference_id(): void
    {
        $year = now()->format('Y');

        Reservation::factory()->create([
            'id_reff'          => 'UID0001',
            'reservation_date' => '2026-06-01',
            'court'            => 'Padel Court VIP Blue 1',
            'reservation_time' => '06:00 - 07:00',
        ]);

        $this->app->instance(ReservationReferenceService::class, new class($year) extends ReservationReferenceService
        {
            /**
             * @var list<string>
             */
            public array $references;

            public function __construct(string $year)
            {
                $this->references = [
                    'UID0001',
                    'UID0002',
                ];
            }

            public function generate(?Carbon $date = null): string
            {
                return array_shift($this->references) ?? 'UID9999';
            }
        });

        $reservation = Reservation::factory()->create([
            'reservation_date' => '2026-06-01',
            'court'            => 'Padel Court VIP Blue 2',
            'reservation_time' => '06:00 - 07:00',
        ]);

        $this->assertSame('UID0002', $reservation->id_reff);
    }

    public function test_reservation_model_normalizes_text_casing_and_spacing(): void
    {
        $reservation = Reservation::factory()->create([
            'customer_name'    => '  budi   santoso ',
            'court'            => 'padel court vip blue 1',
            'reservation_time' => ' 10:00   -   11:00 ',
        ]);

        $this->assertSame('Budi Santoso', $reservation->customer_name);
        $this->assertSame('Padel Court VIP Blue 1', $reservation->court);
        $this->assertSame('10:00 - 11:00', $reservation->reservation_time);
    }

    public function test_reservation_transfer_amount_normalizes_local_decimal_formats(): void
    {
        $this->assertSame('186.818', Reservation::formatTransferAmountForForm('186818.00'));
        $this->assertSame('186818.00', Reservation::normalizeTransferAmount('186818,00'));
        $this->assertSame('186818.00', Reservation::normalizeTransferAmount('186.818,00'));
        $this->assertSame('150000.00', Reservation::normalizeTransferAmount('150.000'));
    }

    public function test_reservation_model_normalizes_legacy_database_time_values(): void
    {
        DB::table('padelnis_reservations')->insert([
            'id_reff'          => 'UID0099',
            'customer_name'    => 'Budi Santoso',
            'reservation_date' => '2026-06-01',
            'court'            => 'Padel Court VIP Blue 1',
            'reservation_time' => '06:00:00',
            'transfer_amount'  => 150000,
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        $reservation = Reservation::query()->where('id_reff', 'UID0099')->firstOrFail();

        $this->assertSame('06:00 - 07:00', $reservation->reservation_time);
    }

    public function test_reservation_time_options_include_multi_hour_blocks(): void
    {
        $options = Reservation::reservableTimeOptions();

        $this->assertArrayHasKey('10:00 - 11:00', $options);
        $this->assertArrayHasKey('10:00 - 12:00', $options);
        $this->assertArrayHasKey('10:00 - 13:00', $options);
        $this->assertArrayHasKey('10:00 - 14:00', $options);
        $this->assertArrayHasKey('10:00 - 15:00', $options);
    }

    public function test_reservation_model_stores_multi_hour_block_as_single_reservation_and_locks_each_hour(): void
    {
        $reservation = Reservation::factory()->create([
            'reservation_date' => '2026-06-01',
            'court'            => 'Padel Court VIP Blue 1',
            'reservation_time' => '10:00 - 13:00',
            'transfer_amount'  => 450000,
        ]);

        $this->assertSame(1, Reservation::query()->count());
        $this->assertSame('10:00 - 13:00', $reservation->reservation_time);
        $this->assertSame('450000.00', $reservation->transfer_amount);
        $this->assertSame([
            '10:00 - 11:00',
            '11:00 - 12:00',
            '12:00 - 13:00',
        ], $reservation->blockedSlotLabels());
        $this->assertSame('10:00 - 11:00, 11:00 - 12:00, 12:00 - 13:00', $reservation->blockedSlotSummary());
        $this->assertDatabaseHas('padelnis_reservation_slots', [
            'reservation_id'   => $reservation->id,
            'active_slot_key'  => Reservation::makeActiveSlotKey('Padel Court VIP Blue 1', '2026-06-01', '10:00 - 11:00'),
        ]);
        $this->assertDatabaseHas('padelnis_reservation_slots', [
            'reservation_id'   => $reservation->id,
            'active_slot_key'  => Reservation::makeActiveSlotKey('Padel Court VIP Blue 1', '2026-06-01', '11:00 - 12:00'),
        ]);
        $this->assertDatabaseHas('padelnis_reservation_slots', [
            'reservation_id'   => $reservation->id,
            'active_slot_key'  => Reservation::makeActiveSlotKey('Padel Court VIP Blue 1', '2026-06-01', '12:00 - 13:00'),
        ]);
        $this->assertSame(3, DB::table('padelnis_reservation_slots')->count());
    }

    public function test_multi_hour_reservation_blocks_overlapping_slots(): void
    {
        Reservation::factory()->create([
            'reservation_date' => '2026-06-01',
            'court'            => 'Padel Court VIP Blue 1',
            'reservation_time' => '10:00 - 13:00',
        ]);

        $this->expectException(ValidationException::class);

        Reservation::factory()->create([
            'reservation_date' => '2026-06-01',
            'court'            => 'Padel Court VIP Blue 1',
            'reservation_time' => '11:00 - 12:00',
        ]);
    }

    public function test_adjacent_slot_after_multi_hour_reservation_can_be_reserved(): void
    {
        Reservation::factory()->create([
            'reservation_date' => '2026-06-01',
            'court'            => 'Padel Court VIP Blue 1',
            'reservation_time' => '10:00 - 13:00',
        ]);

        $reservation = Reservation::factory()->create([
            'reservation_date' => '2026-06-01',
            'court'            => 'Padel Court VIP Blue 1',
            'reservation_time' => '13:00 - 14:00',
        ]);

        $this->assertSame('13:00 - 14:00', $reservation->reservation_time);
        $this->assertSame(2, Reservation::query()->count());
    }

    public function test_active_slot_migration_keeps_first_existing_duplicate_as_the_active_lock(): void
    {
        Schema::dropIfExists('padelnis_reservation_slots');
        Schema::dropIfExists('padelnis_reservations');

        (require base_path('plugins/cesa/padelnis/database/migrations/2026_05_14_000000_create_padelnis_reservations_table.php'))->up();

        DB::table('padelnis_reservations')->insert([
            [
                'id_reff'          => 'UID0091',
                'customer_name'    => 'First Customer',
                'reservation_date' => '2026-06-01',
                'court'            => 'Padel Court VIP Blue 1',
                'reservation_time' => '06:00 - 07:00',
                'transfer_amount'  => 150000,
                'created_at'       => now(),
                'updated_at'       => now(),
                'deleted_at'       => null,
            ],
            [
                'id_reff'          => 'UID0092',
                'customer_name'    => 'Duplicate Customer',
                'reservation_date' => '2026-06-01',
                'court'            => 'Padel Court VIP Blue 1',
                'reservation_time' => '06:00 - 07:00',
                'transfer_amount'  => 150000,
                'created_at'       => now(),
                'updated_at'       => now(),
                'deleted_at'       => null,
            ],
            [
                'id_reff'          => 'UID0093',
                'customer_name'    => 'Deleted Customer',
                'reservation_date' => '2026-06-01',
                'court'            => 'Padel Court VIP Blue 1',
                'reservation_time' => '06:00 - 07:00',
                'transfer_amount'  => 150000,
                'created_at'       => now(),
                'updated_at'       => now(),
                'deleted_at'       => now(),
            ],
        ]);

        (require base_path('plugins/cesa/padelnis/database/migrations/2026_05_14_000001_add_active_slot_key_to_padelnis_reservations_table.php'))->up();

        $reservations = DB::table('padelnis_reservations')
            ->orderBy('id')
            ->get(['active_slot_key']);

        $this->assertSame(
            Reservation::makeActiveSlotKey('Padel Court VIP Blue 1', '2026-06-01', '06:00 - 07:00'),
            $reservations[0]->active_slot_key,
        );
        $this->assertNull($reservations[1]->active_slot_key);
        $this->assertNull($reservations[2]->active_slot_key);
    }

    public function test_reservation_slot_migration_backfills_each_hour_in_a_range(): void
    {
        Schema::dropIfExists('padelnis_reservation_slots');

        DB::table('padelnis_reservations')->insert([
            'id_reff'          => 'UID0094',
            'customer_name'    => 'Block Customer',
            'reservation_date' => '2026-06-01',
            'court'            => 'Padel Court VIP Blue 1',
            'reservation_time' => '10:00 - 13:00',
            'active_slot_key'  => Reservation::makeActiveSlotKey('Padel Court VIP Blue 1', '2026-06-01', '10:00 - 11:00'),
            'transfer_amount'  => 450000,
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        (require base_path('plugins/cesa/padelnis/database/migrations/2026_05_14_000002_create_padelnis_reservation_slots_table.php'))->up();

        $this->assertSame(3, DB::table('padelnis_reservation_slots')->count());
    }

    public function test_reservation_model_blocks_duplicate_active_slots(): void
    {
        Reservation::factory()->create([
            'reservation_date' => '2026-06-01',
            'court'            => 'Padel Court VIP Blue 1',
            'reservation_time' => '06:00 - 07:00',
        ]);

        $this->expectException(ValidationException::class);

        Reservation::factory()->create([
            'reservation_date' => '2026-06-01',
            'court'            => 'Padel Court VIP Blue 1',
            'reservation_time' => '06:00 - 07:00',
        ]);
    }

    public function test_reservation_model_blocks_updates_to_duplicate_active_slots(): void
    {
        Reservation::factory()->create([
            'reservation_date' => '2026-06-01',
            'court'            => 'Padel Court VIP Blue 1',
            'reservation_time' => '06:00 - 07:00',
        ]);

        $secondReservation = Reservation::factory()->create([
            'reservation_date' => '2026-06-01',
            'court'            => 'Padel Court VIP Blue 2',
            'reservation_time' => '06:00 - 07:00',
        ]);

        $this->expectException(ValidationException::class);

        $secondReservation->update([
            'court' => 'Padel Court VIP Blue 1',
        ]);
    }

    public function test_soft_deleted_reservation_slot_can_be_reused(): void
    {
        $deletedReservation = Reservation::factory()->create([
            'reservation_date' => '2026-06-01',
            'court'            => 'Padel Court VIP Blue 1',
            'reservation_time' => '06:00 - 07:00',
        ]);

        $deletedReservation->delete();

        $replacementReservation = Reservation::factory()->create([
            'reservation_date' => '2026-06-01',
            'court'            => 'Padel Court VIP Blue 1',
            'reservation_time' => '06:00 - 07:00',
        ]);

        $this->assertNotSame($deletedReservation->getKey(), $replacementReservation->getKey());
        $this->assertNull(Reservation::withTrashed()->findOrFail($deletedReservation->getKey())->active_slot_key);
        $this->assertNotNull($replacementReservation->active_slot_key);
    }

    public function test_restoring_duplicate_reservation_slot_is_blocked(): void
    {
        $deletedReservation = Reservation::factory()->create([
            'reservation_date' => '2026-06-01',
            'court'            => 'Padel Court VIP Blue 1',
            'reservation_time' => '06:00 - 07:00',
        ]);

        $deletedReservation->delete();

        Reservation::factory()->create([
            'reservation_date' => '2026-06-01',
            'court'            => 'Padel Court VIP Blue 1',
            'reservation_time' => '06:00 - 07:00',
        ]);

        $this->expectException(ValidationException::class);

        $deletedReservation->restore();
    }

    public function test_can_submit_public_reservation_form_and_persist_reservation(): void
    {
        Livewire::test(PublicReservationForm::class)
            ->set('data.customer_name', '  budi   santoso ')
            ->set('data.reservation_date', '2026-06-01')
            ->set('data.court', 'Padel Court VIP Blue 1')
            ->set('data.reservation_time', '10:00 - 11:00')
            ->set('data.transfer_amount', '150000')
            ->call('submit')
            ->assertHasNoErrors()
            ->assertRedirect(URL::signedRoute('padelnis.public.success', [
                'idReff' => 'UID0001',
            ]));

        $reservation = Reservation::query()->firstOrFail();

        $this->assertSame('UID0001', $reservation->id_reff);
        $this->assertSame('Budi Santoso', $reservation->customer_name);
        $this->assertSame('2026-06-01', $reservation->reservation_date->format('Y-m-d'));
        $this->assertSame('Padel Court VIP Blue 1', $reservation->court);
        $this->assertSame('10:00 - 11:00', $reservation->reservation_time);
        $this->assertSame('150000.00', $reservation->transfer_amount);
        $this->assertNotNull($reservation->created_at);
        $this->assertFalse(session()->has('filament.notifications'));
    }

    public function test_can_submit_public_multi_hour_reservation_as_one_payment(): void
    {
        Livewire::test(PublicReservationForm::class)
            ->set('data.customer_name', 'Budi Santoso')
            ->set('data.reservation_date', '2026-06-01')
            ->set('data.court', 'Padel Court VIP Blue 1')
            ->set('data.reservation_time', '10:00 - 13:00')
            ->set('data.transfer_amount', '450000')
            ->call('submit')
            ->assertHasNoErrors();

        $reservation = Reservation::query()->firstOrFail();

        $this->assertSame(1, Reservation::query()->count());
        $this->assertSame('10:00 - 13:00', $reservation->reservation_time);
        $this->assertSame('450000.00', $reservation->transfer_amount);
        $this->assertSame(3, DB::table('padelnis_reservation_slots')->count());
    }

    public function test_public_reservation_success_page_shows_blocked_slot_details(): void
    {
        $reservation = Reservation::factory()->create([
            'customer_name'    => 'Uji Blok',
            'reservation_date' => '2026-05-17',
            'court'            => 'Padel Court VIP Blue 1',
            'reservation_time' => '10:00 - 13:00',
            'transfer_amount'  => 450000,
        ]);

        $this->get(URL::signedRoute('padelnis.public.success', ['idReff' => $reservation->id_reff]))
            ->assertOk()
            ->assertSee(__('padelnis::filament/resources/reservation.fields.blocked_slots'))
            ->assertSee('10:00 - 11:00, 11:00 - 12:00, 12:00 - 13:00');
    }

    public function test_can_submit_public_reservation_form_with_local_decimal_transfer_amount(): void
    {
        Livewire::test(PublicReservationForm::class)
            ->set('data.customer_name', 'Budi Santoso')
            ->set('data.reservation_date', '2026-06-01')
            ->set('data.court', 'Padel Court VIP Blue 1')
            ->set('data.reservation_time', '10:00 - 11:00')
            ->set('data.transfer_amount', '186818,00')
            ->call('submit')
            ->assertHasNoErrors();

        $reservation = Reservation::query()->firstOrFail();

        $this->assertSame('186818.00', $reservation->transfer_amount);
    }

    public function test_public_reservation_form_blocks_duplicate_active_slots(): void
    {
        Reservation::factory()->create([
            'reservation_date' => '2026-06-01',
            'court'            => 'Padel Court VIP Blue 1',
            'reservation_time' => '10:00 - 11:00',
        ]);

        Livewire::test(PublicReservationForm::class)
            ->set('data.customer_name', 'Budi Santoso')
            ->set('data.reservation_date', '2026-06-01')
            ->set('data.court', 'Padel Court VIP Blue 1')
            ->set('data.reservation_time', '10:00 - 11:00')
            ->set('data.transfer_amount', '150000')
            ->call('submit')
            ->assertHasErrors(['data.reservation_time']);

        $this->assertSame(1, Reservation::query()->count());
    }

    public function test_can_render_public_reservation_success_page_on_dedicated_url(): void
    {
        $reservation = Reservation::factory()->create([
            'customer_name'    => 'Uji Coba',
            'reservation_date' => '2026-05-17',
            'court'            => 'Padel Court VIP Blue 1',
            'reservation_time' => '06:00 - 07:00',
            'transfer_amount'  => 10000,
        ]);

        $this->get(URL::signedRoute('padelnis.public.success', ['idReff' => $reservation->id_reff]))
            ->assertOk()
            ->assertSee(__('padelnis::views/public-reservation-form.summary.title'))
            ->assertSee($reservation->id_reff)
            ->assertSee('Uji Coba')
            ->assertSee('Rp 10.000');
    }

    public function test_public_reservation_success_page_requires_signed_url(): void
    {
        $reservation = Reservation::factory()->create();

        $this->get(route('padelnis.public.success', ['idReff' => $reservation->id_reff]))
            ->assertForbidden();
    }

    public function test_public_reservation_form_requires_reservation_fields(): void
    {
        Livewire::test(PublicReservationForm::class)
            ->call('submit')
            ->assertHasErrors([
                'data.customer_name',
                'data.reservation_date',
                'data.court',
                'data.reservation_time',
                'data.transfer_amount',
            ]);
    }
}
