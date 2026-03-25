<?php

namespace Cesa\Payroll\Filament\Resources;

use Cesa\Payroll\Filament\Resources\PayrollPeriodResource\Pages;
use Cesa\Payroll\Filament\Resources\PayrollPeriodResource\RelationManagers\PayrollRecordsRelationManager;
use Cesa\Payroll\Models\PayrollPeriod;
use Cesa\Payroll\Services\GeneratePayrollService;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class PayrollPeriodResource extends PayrollResource
{
    protected static ?string $model = PayrollPeriod::class;

    protected static string|\BackedEnum|null $navigationIcon = null;

    protected static ?int $navigationSort = 4;

    public static function getNavigationLabel(): string
    {
        return __('payroll::filament/resources/payroll-period.navigation.label');
    }

    public static function getModelLabel(): string
    {
        return __('payroll::filament/resources/payroll-period.model.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('payroll::filament/resources/payroll-period.model.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\TextInput::make('name')
                    ->label(__('payroll::filament/resources/payroll-period.form.fields.name'))
                    ->required()
                    ->maxLength(255),
                Forms\Components\DatePicker::make('start_date')
                    ->label(__('payroll::filament/resources/payroll-period.form.fields.start_date'))
                    ->required(),
                Forms\Components\DatePicker::make('end_date')
                    ->label(__('payroll::filament/resources/payroll-period.form.fields.end_date'))
                    ->required(),
                Forms\Components\Select::make('status')
                    ->label(__('payroll::filament/resources/payroll-period.form.fields.status'))
                    ->options([
                        'open'   => __('payroll::enums/status.open'),
                        'locked' => __('payroll::enums/status.locked'),
                        'paid'   => __('payroll::enums/status.paid'),
                    ])
                    ->required()
                    ->default('open')
                    ->hiddenOn('create')
                    ->dehydrated(),
                Forms\Components\Toggle::make('auto_generate')
                    ->label(__('payroll::filament/resources/payroll-period.form.fields.auto_generate'))
                    ->helperText(__('payroll::filament/resources/payroll-period.form.fields.auto_generate_helper'))
                    ->default(false)
                    ->visibleOn('create'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('payroll::filament/resources/payroll-period.table.columns.name'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('start_date')
                    ->label(__('payroll::filament/resources/payroll-period.table.columns.start_date'))
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('end_date')
                    ->label(__('payroll::filament/resources/payroll-period.table.columns.end_date'))
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('payroll::filament/resources/payroll-period.table.columns.status'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'open'   => 'gray',
                        'locked' => 'warning',
                        'paid'   => 'success',
                        default  => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('payroll::filament/resources/payroll-period.table.columns.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordUrl(fn (PayrollPeriod $record): string => static::getUrl('view', ['record' => $record]))
            ->actions([
                Actions\EditAction::make()
                    ->modal()
                    ->slideOver()
                    ->modalWidth('md')
                    ->schema(fn (Schema $schema): Schema => static::form($schema->columns(1))),
                Actions\Action::make('generate_payroll')
                    ->label(__('payroll::filament/resources/payroll-period.table.actions.generate_payroll.label'))
                    ->icon('heroicon-o-cpu-chip')
                    ->color('info')
                    ->requiresConfirmation()
                    ->modalHeading(__('payroll::filament/resources/payroll-period.table.actions.generate_payroll.modal_heading'))
                    ->modalDescription(__('payroll::filament/resources/payroll-period.table.actions.generate_payroll.modal_description'))
                    ->visible(fn (PayrollPeriod $record): bool => $record->status === 'open')
                    ->action(function (PayrollPeriod $record, GeneratePayrollService $service) {
                        try {
                            $service->generate($record);

                            Notification::make()
                                ->title(__('payroll::filament/resources/payroll-period.notifications.payroll_generated.title'))
                                ->body(__('payroll::filament/resources/payroll-period.notifications.payroll_generated.body'))
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title(__('payroll::filament/resources/payroll-period.notifications.generate_failed.title'))
                                ->body(__('payroll::filament/resources/payroll-period.notifications.generate_failed.body', ['message' => $e->getMessage()]))
                                ->danger()
                                ->send();
                        }
                    }),
                Actions\Action::make('mark_as_paid')
                    ->label(__('payroll::filament/resources/payroll-period.table.actions.mark_as_paid.label'))
                    ->icon('heroicon-o-currency-dollar')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading(__('payroll::filament/resources/payroll-period.table.actions.mark_as_paid.label'))
                    ->modalDescription(__('payroll::filament/resources/payroll-period.table.actions.mark_as_paid.modal_description'))
                    ->visible(fn (PayrollPeriod $record): bool => $record->status === 'locked')
                    ->action(function (PayrollPeriod $record) {
                        $record->update(['status' => 'paid']);

                        Notification::make()
                            ->title(__('payroll::filament/resources/payroll-period.notifications.marked_as_paid.title'))
                            ->body(__('payroll::filament/resources/payroll-period.notifications.marked_as_paid.body'))
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            PayrollRecordsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPayrollPeriods::route('/'),
            'view'  => Pages\ViewPayrollPeriod::route('/{record}'),
        ];
    }
}
