<?php

namespace Cesa\Rekrutmen\Filament\Resources\JobApplicationResource\RelationManagers;

use Cesa\Rekrutmen\Models\JobApplication;
use Cesa\Rekrutmen\Models\JobApplicationHistory;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;

class HistoriesRelationManager extends RelationManager
{
    protected static string $relationship = 'histories';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return true;
    }

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('rekrutmen::filament/resources/job-application/relation-managers/histories.title');
    }

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns([
                'default' => 1,
                'md'      => 2,
            ])
            ->schema([
                Forms\Components\DatePicker::make('activity_date')
                    ->label(__('rekrutmen::filament/resources/job-application/relation-managers/histories.form.fields.activity_date'))
                    ->required()
                    
                    ->columnSpan([
                        'default' => 1,
                        'md'      => 2,
                    ]),
                Forms\Components\Textarea::make('notes')
                    ->label(__('rekrutmen::filament/resources/job-application/relation-managers/histories.form.fields.notes'))
                    ->maxLength(65535)
                    ->columnSpan([
                        'default' => 1,
                        'md'      => 2,
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('status')
            ->columns([
                Tables\Columns\TextColumn::make('activity_title')
                    ->label(__('rekrutmen::filament/resources/job-application/relation-managers/histories.columns.activity'))
                    ->state(fn (JobApplicationHistory $record): string => $record->activity_title ?: $record->activityLabel())
                    ->placeholder(__('rekrutmen::filament/resources/job-application/relation-managers/histories.placeholders.activity')),
                Tables\Columns\TextColumn::make('result')
                    ->label(__('rekrutmen::filament/resources/job-application/relation-managers/histories.columns.result'))
                    ->badge()
                    ->placeholder(__('rekrutmen::filament/resources/job-application/relation-managers/histories.placeholders.result')),
                Tables\Columns\TextColumn::make('fromStage.name')
                    ->label(__('rekrutmen::filament/resources/job-application/relation-managers/histories.columns.from_stage'))
                    ->placeholder(__('rekrutmen::filament/resources/job-application/relation-managers/histories.placeholders.from_stage')),
                Tables\Columns\TextColumn::make('toStage.name')
                    ->label(__('rekrutmen::filament/resources/job-application/relation-managers/histories.columns.to_stage'))
                    ->placeholder(__('rekrutmen::filament/resources/job-application/relation-managers/histories.placeholders.to_stage')),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('rekrutmen::filament/resources/job-application/relation-managers/histories.columns.status'))
                    ->state(fn (JobApplicationHistory $record): ?string => $record->activityStatusLabel())
                    ->badge()
                    ->color(fn (JobApplicationHistory $record): string|array|null => $record->activityStatusColor()),
                Tables\Columns\TextColumn::make('notes')
                    ->label(__('rekrutmen::filament/resources/job-application/relation-managers/histories.columns.notes'))
                    ->limit(50),
                Tables\Columns\TextColumn::make('performer.name')
                    ->label(__('rekrutmen::filament/resources/job-application/relation-managers/histories.columns.performed_by')),
                Tables\Columns\TextColumn::make('activity_date')
                    ->label(__('rekrutmen::filament/resources/job-application/relation-managers/histories.columns.activity_date'))
                    ->state(fn (JobApplicationHistory $record): ?Carbon => $record->activity_date ?? $record->created_at)
                    ->date('d M Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('rekrutmen::filament/resources/job-application/relation-managers/histories.columns.recorded_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                // Readonly, records created via actions
            ])
            ->recordActions([
                EditAction::make()
                    ->label(__('rekrutmen::filament/resources/job-application/relation-managers/histories.actions.edit.label'))
                    ->modalHeading(__('rekrutmen::filament/resources/job-application/relation-managers/histories.actions.edit.heading'))
                    ->slideOver()
                    ->modalWidth('md')
                    ->visible(fn (JobApplicationHistory $record): bool => Gate::allows('update', $record))
                    ->mutateRecordDataUsing(function (JobApplicationHistory $record, array $data): array {
                        $data['activity_date'] = ($record->activity_date ?? $record->created_at)?->toDateString();

                        return $data;
                    })
                    ->using(function (JobApplicationHistory $record, array $data): JobApplicationHistory {
                        $activityDate = Carbon::parse($data['activity_date'])->toDateString();
                        $notes = $data['notes'] ?? null;

                        if (filled($record->activity_group_id)) {
                            $activityTitle = $this->resolveBatchActivityTitle($record, $activityDate);

                            JobApplicationHistory::query()
                                ->where('activity_group_id', $record->activity_group_id)
                                ->update([
                                    'activity_date'  => $activityDate,
                                    'activity_title' => $activityTitle,
                                    'updated_at'     => now(),
                                ]);
                        }

                        $record->update([
                            'activity_date' => $activityDate,
                            'notes'         => $notes,
                        ]);

                        Notification::make()
                            ->title(__('rekrutmen::filament/resources/job-application/relation-managers/histories.notifications.updated'))
                            ->success()
                            ->send();

                        return $record;
                    }),
            ])
            ->bulkActions([
                // Readonly
            ]);
    }

    private function resolveBatchActivityTitle(JobApplicationHistory $record, string $activityDate): ?string
    {
        if (! is_string($record->activity_title) || $record->activity_title === '') {
            return null;
        }

        return JobApplication::generateBatchActivityTitle($record->activityLabel(), $activityDate);
    }
}
