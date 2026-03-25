<?php

namespace Cesa\Payroll\Filament\Resources\PayrollPeriodResource\RelationManagers;

use Cesa\Payroll\Filament\Resources\PayrollRecordResource;
use Filament\Actions;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class PayrollRecordsRelationManager extends RelationManager
{
    protected static string $relationship = 'records';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('payroll::filament/resources/payroll-record.model.plural');
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label(__('payroll::filament/resources/payroll-record.table.columns.employee'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_attendance_days')
                    ->label(__('payroll::filament/resources/payroll-record.table.columns.total_attendance_days'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_overtime_hours')
                    ->label(__('payroll::filament/resources/payroll-record.table.columns.total_overtime_hours'))
                    ->numeric(2)
                    ->sortable(),
                Tables\Columns\TextColumn::make('gross_salary')
                    ->label(__('payroll::filament/resources/payroll-record.table.columns.base_salary'))
                    ->money('IDR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_penalties')
                    ->label(__('payroll::filament/resources/payroll-record.table.columns.late_penalty'))
                    ->money('IDR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('net_salary')
                    ->label(__('payroll::filament/resources/payroll-record.table.columns.net_salary'))
                    ->money('IDR')
                    ->sortable()
                    ->weight('bold'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                //
            ])
            ->actions([
                Actions\Action::make('view')
                    ->label(__('filament-actions::view.single.label'))
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->url(fn ($record) => PayrollRecordResource::getUrl('view', ['record' => $record])),
            ])
            ->bulkActions([
                //
            ]);
    }
}
