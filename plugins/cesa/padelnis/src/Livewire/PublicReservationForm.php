<?php

namespace Cesa\Padelnis\Livewire;

use Cesa\Padelnis\Models\Reservation;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Pages\Concerns\InteractsWithFormActions;
use Filament\Pages\SimplePage;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;
use Webkul\PluginManager\Package;

class PublicReservationForm extends SimplePage
{
    use InteractsWithFormActions;
    use InteractsWithForms;

    protected static string $layout = 'padelnis::layouts.form';

    protected string $view = 'padelnis::livewire.public-reservation-form';

    public ?array $data = [];

    public function mount(): void
    {
        if (! Package::isPluginInstalled('padelnis')) {
            abort(404);
        }

        $this->form->fill($this->data);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('customer_name')
                    ->label(__('padelnis::filament/resources/reservation.fields.customer_name'))
                    ->required()
                    ->maxLength(255)
                    ->placeholder(__('padelnis::views/public-reservation-form.placeholders.customer_name')),
                DatePicker::make('reservation_date')
                    ->label(__('padelnis::filament/resources/reservation.fields.reservation_date'))
                    ->required()
                    ->displayFormat('Y-m-d')
                    ->native(false)
                    ->placeholder(__('padelnis::views/public-reservation-form.placeholders.reservation_date')),
                Select::make('court')
                    ->label(__('padelnis::filament/resources/reservation.fields.court'))
                    ->options(fn (): array => Reservation::courtOptions())
                    ->searchable()
                    ->required()
                    ->rule(fn () => Rule::in(array_keys(Reservation::courtOptions())))
                    ->placeholder(__('padelnis::views/public-reservation-form.placeholders.court')),
                Select::make('reservation_time')
                    ->label(__('padelnis::filament/resources/reservation.fields.reservation_time'))
                    ->options(fn (): array => Reservation::reservableTimeOptions())
                    ->searchable()
                    ->required()
                    ->rule(fn () => Rule::in(array_keys(Reservation::reservableTimeOptions())))
                    ->rule(static fn (Get $get): Closure => static::activeSlotValidationRule($get))
                    ->placeholder(__('padelnis::views/public-reservation-form.placeholders.reservation_time')),
                TextInput::make('transfer_amount')
                    ->label(__('padelnis::filament/resources/reservation.fields.transfer_amount'))
                    ->inputMode('numeric')
                    ->prefix('Rp')
                    ->required()
                    ->rule('numeric')
                    ->rule('min:0')
                    ->placeholder(__('padelnis::views/public-reservation-form.placeholders.transfer_amount'))
                    ->formatStateUsing(fn (mixed $state): ?string => Reservation::formatTransferAmountForForm($state))
                    ->mutateStateForValidationUsing(fn (mixed $state): ?string => Reservation::normalizeTransferAmount($state))
                    ->dehydrateStateUsing(fn (mixed $state): ?string => Reservation::normalizeTransferAmount($state))
                    ->extraAlpineAttributes([
                        'x-on:input' => static::transferAmountMaskAlpineExpression(),
                        'x-on:blur'  => static::transferAmountMaskAlpineExpression(),
                        'x-init'     => static::transferAmountMaskAlpineExpression(),
                    ]),
                DatePicker::make('transfer_date')
                    ->label(__('padelnis::filament/resources/reservation.fields.transfer_date'))
                    ->required()
                    ->displayFormat('Y-m-d')
                    ->native(false)
                    ->placeholder(__('padelnis::views/public-reservation-form.placeholders.transfer_date')),
                Textarea::make('notes')
                    ->label(__('padelnis::filament/resources/reservation.fields.notes'))
                    ->nullable()
                    ->maxLength(1000)
                    ->rows(3)
                    ->placeholder(__('padelnis::views/public-reservation-form.placeholders.notes')),
            ])
            ->statePath('data');
    }

    public function submit(): mixed
    {
        $state = $this->form->getState();

        try {
            $reservation = Reservation::query()->create([
                'customer_name'    => Arr::get($state, 'customer_name'),
                'reservation_date' => Arr::get($state, 'reservation_date'),
                'court'            => Arr::get($state, 'court'),
                'reservation_time' => Arr::get($state, 'reservation_time'),
                'transfer_amount'  => Arr::get($state, 'transfer_amount'),
                'transfer_date'    => Arr::get($state, 'transfer_date'),
                'notes'            => Arr::get($state, 'notes'),
            ]);

            $this->resetFormAfterSubmission();

            return redirect()->to(URL::signedRoute('padelnis.public.success', [
                'idReff' => $reservation->id_reff,
            ]));
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            Log::error('Failed to submit Padelnis reservation.', [
                'error' => $exception->getMessage(),
            ]);

            $this->addError('data', __('padelnis::views/public-reservation-form.messages.generic'));

            return null;
        }
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('submit')
                ->label(__('padelnis::views/public-reservation-form.actions.submit'))
                ->extraAttributes([
                    'class' => '!bg-primary-700 !text-white shadow-sm hover:!bg-primary-800 hover:!text-white focus-visible:!ring-primary-300',
                ], merge: true)
                ->submit('submit'),
        ];
    }

    protected static function transferAmountMaskAlpineExpression(): string
    {
        return <<<'JS'
const value = String($el.value);
const integer = value.replace(/,\d{0,2}$/, '').replace(/\D/g, '');
$el.value = integer.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
JS;
    }

    protected function resetFormAfterSubmission(): void
    {
        $this->data = [];
        $this->form->fill($this->data);
    }

    protected static function activeSlotValidationRule(Get $get): Closure
    {
        return static function (string $attribute, mixed $value, Closure $fail) use ($get): void {
            if (Reservation::activeSlotExists($get('court'), $get('reservation_date'), $value)) {
                $fail(__('padelnis::filament/resources/reservation.validation.active_slot_unique'));
            }
        };
    }
}
