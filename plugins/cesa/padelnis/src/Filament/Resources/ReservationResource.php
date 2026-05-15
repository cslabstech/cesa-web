<?php

namespace Cesa\Padelnis\Filament\Resources;

use Cesa\Padelnis\Filament\Resources\ReservationResource\Pages;
use Cesa\Padelnis\Models\Reservation;
use Closure;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Webkul\PluginManager\Package;
use Webkul\Security\Traits\HasResourcePermissionQuery;

class ReservationResource extends Resource
{
    use HasResourcePermissionQuery;

    protected static ?string $model = Reservation::class;

    protected static string|\BackedEnum|null $navigationIcon = null;

    protected static ?int $navigationSort = 1;

    public static function shouldRegisterNavigation(): bool
    {
        return Package::isPluginInstalled('padelnis');
    }

    public static function getNavigationLabel(): string
    {
        return __('padelnis::filament/resources/reservation.navigation.title');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('admin.navigation.padelnis');
    }

    public static function getModelLabel(): string
    {
        return __('padelnis::filament/resources/reservation.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('padelnis::filament/resources/reservation.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make(__('padelnis::filament/resources/reservation.form.sections.reservation.title'))
                    ->schema([
                        TextInput::make('id_reff')
                            ->label(__('padelnis::filament/resources/reservation.fields.id_reff'))
                            ->disabled()
                            ->dehydrated(false)
                            ->visible(fn (string $operation): bool => $operation !== 'create'),
                        TextInput::make('customer_name')
                            ->label(__('padelnis::filament/resources/reservation.fields.customer_name'))
                            ->required()
                            ->maxLength(255)
                            ->placeholder(__('padelnis::filament/resources/reservation.form.placeholders.customer_name')),
                        DatePicker::make('reservation_date')
                            ->label(__('padelnis::filament/resources/reservation.fields.reservation_date'))
                            ->required()
                            ->native(false)
                            ->displayFormat('Y-m-d')
                            ->placeholder(__('padelnis::filament/resources/reservation.form.placeholders.reservation_date')),
                        Select::make('court')
                            ->label(__('padelnis::filament/resources/reservation.fields.court'))
                            ->options(fn (): array => Reservation::courtOptions())
                            ->searchable()
                            ->required()
                            ->rule(fn () => Rule::in(array_keys(Reservation::courtOptions())))
                            ->placeholder(__('padelnis::filament/resources/reservation.form.placeholders.court')),
                        Select::make('reservation_time')
                            ->label(__('padelnis::filament/resources/reservation.fields.reservation_time'))
                            ->options(fn (): array => Reservation::reservableTimeOptions())
                            ->searchable()
                            ->required()
                            ->rule(fn () => Rule::in(array_keys(Reservation::reservableTimeOptions())))
                            ->rule(static fn (Get $get, ?Reservation $record = null): Closure => static::activeSlotValidationRule($get, $record))
                            ->placeholder(__('padelnis::filament/resources/reservation.form.placeholders.reservation_time')),
                        TextInput::make('transfer_amount')
                            ->label(__('padelnis::filament/resources/reservation.fields.transfer_amount'))
                            ->inputMode('numeric')
                            ->prefix('Rp')
                            ->required()
                            ->rule('numeric')
                            ->rule('min:0')
                            ->placeholder(__('padelnis::filament/resources/reservation.form.placeholders.transfer_amount'))
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
                            ->native(false)
                            ->displayFormat('Y-m-d')
                            ->placeholder(__('padelnis::filament/resources/reservation.form.placeholders.transfer_date')),
                        Textarea::make('notes')
                            ->label(__('padelnis::filament/resources/reservation.fields.notes'))
                            ->nullable()
                            ->maxLength(1000)
                            ->rows(3)
                            ->placeholder(__('padelnis::filament/resources/reservation.form.placeholders.notes'))
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('padelnis::filament/resources/reservation.form.sections.reservation.title'))
                    ->schema([
                        TextEntry::make('id_reff')
                            ->label(__('padelnis::filament/resources/reservation.fields.id_reff'))
                            ->copyable(),
                        TextEntry::make('customer_name')
                            ->label(__('padelnis::filament/resources/reservation.fields.customer_name')),
                        TextEntry::make('reservation_date')
                            ->label(__('padelnis::filament/resources/reservation.fields.reservation_date'))
                            ->date('d M Y'),
                        TextEntry::make('court')
                            ->label(__('padelnis::filament/resources/reservation.fields.court')),
                        TextEntry::make('reservation_time')
                            ->label(__('padelnis::filament/resources/reservation.fields.reservation_time')),
                        TextEntry::make('blocked_slots')
                            ->label(__('padelnis::filament/resources/reservation.fields.blocked_slots'))
                            ->state(fn (Reservation $record): array => $record->blockedSlotLabels())
                            ->badge()
                            ->separator(', '),
                        TextEntry::make('transfer_amount')
                            ->label(__('padelnis::filament/resources/reservation.fields.transfer_amount'))
                            ->money('IDR'),
                        TextEntry::make('transfer_date')
                            ->label(__('padelnis::filament/resources/reservation.fields.transfer_date'))
                            ->date('d M Y')
                            ->placeholder('-'),
                        TextEntry::make('notes')
                            ->label(__('padelnis::filament/resources/reservation.fields.notes'))
                            ->placeholder('-')
                            ->columnSpanFull(),
                        TextEntry::make('created_at')
                            ->label(__('padelnis::filament/resources/reservation.fields.created_at'))
                            ->dateTime('d M Y H:i'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('id_reff')
                    ->label(__('padelnis::filament/resources/reservation.table.columns.id_reff'))
                    ->searchable()
                    ->copyable()
                    ->copyMessage(__('padelnis::filament/resources/reservation.actions.copy_id_reff'))
                    ->sortable(),
                TextColumn::make('customer_name')
                    ->label(__('padelnis::filament/resources/reservation.table.columns.customer_name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('reservation_date')
                    ->label(__('padelnis::filament/resources/reservation.table.columns.reservation_date'))
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('reservation_time')
                    ->label(__('padelnis::filament/resources/reservation.table.columns.reservation_time'))
                    ->sortable(),
                TextColumn::make('blocked_slots')
                    ->label(__('padelnis::filament/resources/reservation.table.columns.blocked_slots'))
                    ->state(fn (Reservation $record): string => $record->blockedSlotSummary())
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('court')
                    ->label(__('padelnis::filament/resources/reservation.table.columns.court'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('transfer_amount')
                    ->label(__('padelnis::filament/resources/reservation.table.columns.transfer_amount'))
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('transfer_date')
                    ->label(__('padelnis::filament/resources/reservation.table.columns.transfer_date'))
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('notes')
                    ->label(__('padelnis::filament/resources/reservation.table.columns.notes'))
                    ->limit(60)
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label(__('padelnis::filament/resources/reservation.table.columns.created_at'))
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Filter::make('reservation_date')
                    ->form([
                        DatePicker::make('reservation_from')
                            ->label(__('padelnis::filament/resources/reservation.filters.reservation_from')),
                        DatePicker::make('reservation_until')
                            ->label(__('padelnis::filament/resources/reservation.filters.reservation_until')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['reservation_from'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('reservation_date', '>=', $date))
                            ->when($data['reservation_until'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('reservation_date', '<=', $date));
                    })
                    ->indicateUsing(function (array $data): ?string {
                        $from = $data['reservation_from'] ?? null;
                        $until = $data['reservation_until'] ?? null;

                        if (! $from && ! $until) {
                            return null;
                        }

                        $fromLabel = $from ? Carbon::parse($from)->format('d M Y') : null;
                        $untilLabel = $until ? Carbon::parse($until)->format('d M Y') : null;

                        if ($fromLabel && $untilLabel) {
                            return __('padelnis::filament/resources/reservation.filters.reservation_range', [
                                'from'  => $fromLabel,
                                'until' => $untilLabel,
                            ]);
                        }

                        return $fromLabel
                            ? __('padelnis::filament/resources/reservation.filters.reservation_from_value', ['date' => $fromLabel])
                            : __('padelnis::filament/resources/reservation.filters.reservation_until_value', ['date' => $untilLabel]);
                    }),
                SelectFilter::make('court')
                    ->label(__('padelnis::filament/resources/reservation.filters.court'))
                    ->options(fn (): array => Reservation::courtOptions())
                    ->searchable(),
                SelectFilter::make('reservation_time')
                    ->label(__('padelnis::filament/resources/reservation.filters.reservation_time'))
                    ->options(fn (): array => Reservation::reservableTimeOptions())
                    ->searchable(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListReservations::route('/'),
            'create' => Pages\CreateReservation::route('/create'),
            'view'   => Pages\ViewReservation::route('/{record}'),
            'edit'   => Pages\EditReservation::route('/{record}/edit'),
        ];
    }

    protected static function activeSlotValidationRule(Get $get, ?Reservation $record = null): Closure
    {
        return static function (string $attribute, mixed $value, Closure $fail) use ($get, $record): void {
            if (Reservation::activeSlotExists($get('court'), $get('reservation_date'), $value, $record?->getKey())) {
                $fail(__('padelnis::filament/resources/reservation.validation.active_slot_unique'));
            }
        };
    }

    protected static function transferAmountMaskAlpineExpression(): string
    {
        return <<<'JS'
const value = String($el.value);
const integer = value.replace(/,\d{0,2}$/, '').replace(/\D/g, '');
$el.value = integer.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
JS;
    }
}
