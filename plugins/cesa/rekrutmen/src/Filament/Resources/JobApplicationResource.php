<?php

namespace Cesa\Rekrutmen\Filament\Resources;

use Cesa\Rekrutmen\Enums\JobApplicationGender;
use Cesa\Rekrutmen\Enums\JobApplicationMaritalStatus;
use Cesa\Rekrutmen\Enums\JobApplicationStatus;
use Cesa\Rekrutmen\Filament\Resources\JobApplicationResource\Pages;
use Cesa\Rekrutmen\Filament\Resources\JobApplicationResource\RelationManagers\HistoriesRelationManager;
use Cesa\Rekrutmen\Models\JobApplication;
use Cesa\Rekrutmen\Models\RekrutmenStage;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\URL;
use League\Flysystem\UnableToCheckFileExistence;

class JobApplicationResource extends Resource
{
    protected static ?string $model = JobApplication::class;

    protected static \BackedEnum|string|null $navigationIcon = null;

    protected static ?int $navigationSort = 3;

    public static function getNavigationGroup(): string
    {
        return __('admin.navigation.rekrutmen');
    }

    public static function getNavigationLabel(): string
    {
        return __('rekrutmen::filament/resources/job-application.navigation.label');
    }

    public static function getModelLabel(): string
    {
        return __('rekrutmen::filament/resources/job-application.model.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('rekrutmen::filament/resources/job-application.model.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Grid::make(3)->schema([
                    Group::make([
                        Section::make(__('rekrutmen::filament/resources/job-application.form.sections.candidate_information'))
                            ->schema([
                                Forms\Components\TextInput::make('full_name')
                                    ->label(__('rekrutmen::filament/resources/job-application.form.fields.full_name'))
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('email')
                                    ->label(__('rekrutmen::filament/resources/job-application.form.fields.email'))
                                    ->email()
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\Select::make('gender')
                                    ->label(__('rekrutmen::filament/resources/job-application.form.fields.gender'))
                                    ->options(JobApplicationGender::class)
                                    ->required(),
                                Forms\Components\DatePicker::make('birth_date')
                                    ->label(__('rekrutmen::filament/resources/job-application.form.fields.birth_date'))
                                    ->required(),
                                Forms\Components\Select::make('marital_status')
                                    ->label(__('rekrutmen::filament/resources/job-application.form.fields.marital_status'))
                                    ->options(JobApplicationMaritalStatus::class)
                                    ->required(),
                                Forms\Components\TextInput::make('whatsapp_number')
                                    ->label(__('rekrutmen::filament/resources/job-application.form.fields.whatsapp_number'))
                                    ->tel()
                                    ->required()
                                    ->maxLength(30),
                                Forms\Components\TextInput::make('active_phone')
                                    ->label(__('rekrutmen::filament/resources/job-application.form.fields.active_phone'))
                                    ->tel()
                                    ->required()
                                    ->maxLength(30),
                                Forms\Components\TextInput::make('emergency_contact_name')
                                    ->label(__('rekrutmen::filament/resources/job-application.form.fields.emergency_contact_name'))
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('emergency_contact_relation')
                                    ->label(__('rekrutmen::filament/resources/job-application.form.fields.emergency_contact_relation'))
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('emergency_contact_phone')
                                    ->label(__('rekrutmen::filament/resources/job-application.form.fields.emergency_contact_phone'))
                                    ->tel()
                                    ->required()
                                    ->maxLength(30),
                                Forms\Components\Textarea::make('address_ktp')
                                    ->label(__('rekrutmen::filament/resources/job-application.form.fields.address_ktp'))
                                    ->required()
                                    ->columnSpanFull(),
                                Forms\Components\Textarea::make('address_domicile')
                                    ->label(__('rekrutmen::filament/resources/job-application.form.fields.address_domicile'))
                                    ->required()
                                    ->columnSpanFull(),
                            ])->columns(2),
                    ])->columnSpan(2),

                    Group::make([
                        Section::make(__('rekrutmen::filament/resources/job-application.form.sections.application_details'))
                            ->schema([
                                Forms\Components\Select::make('job_posting_id')
                                    ->relationship('jobPosting', 'title')
                                    ->required()
                                    ->searchable()
                                    ->live()
                                    ->disabled(fn (string $operation): bool => $operation === 'edit')
                                    ->afterStateUpdated(function (mixed $state, Forms\Set $set): void {
                                        $set('current_stage_id', JobApplication::resolveInitialStageIdForJobPostingId($state));
                                    })
                                    ->label(__('rekrutmen::filament/resources/job-application.form.fields.job_posting_id')),
                                Forms\Components\Select::make('current_stage_id')
                                    ->relationship('currentStage', 'name')
                                    ->label(__('rekrutmen::filament/resources/job-application.form.fields.current_stage_id'))
                                    ->disabled()
                                    ->dehydrated(),
                                Forms\Components\Select::make('status')
                                    ->label(__('rekrutmen::filament/resources/job-application.form.fields.status'))
                                    ->required()
                                    ->options(JobApplicationStatus::class)
                                    ->default(JobApplicationStatus::IN_PROGRESS)
                                    ->disabled()
                                    ->dehydrated(fn (string $operation): bool => $operation === 'create'),
                                Forms\Components\FileUpload::make('photo_path')
                                    ->label(__('rekrutmen::filament/resources/job-application.form.fields.photo_path'))
                                    ->disk(JobApplication::resumeDisk())
                                    ->directory(JobApplication::PHOTO_DIRECTORY)
                                    ->image()
                                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                    ->maxSize(5120)
                                    ->visibility('private')
                                    ->downloadable()
                                    ->openable()
                                    ->getUploadedFileUsing(fn (?JobApplication $record, string $file, string|array|null $storedFileNames, $component): ?array => self::resolveUploadedFileMetadata($record, $file, $storedFileNames, $component, 'photo')),
                                Forms\Components\FileUpload::make('resume_path')
                                    ->label(__('rekrutmen::filament/resources/job-application.form.fields.resume_path'))
                                    ->disk(JobApplication::resumeDisk())
                                    ->directory(JobApplication::RESUME_DIRECTORY)
                                    ->acceptedFileTypes(['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'])
                                    ->maxSize(5120) // 5MB
                                    ->visibility('private')
                                    ->downloadable()
                                    ->openable()
                                    ->getUploadedFileUsing(fn (?JobApplication $record, string $file, string|array|null $storedFileNames, $component): ?array => self::resolveUploadedFileMetadata($record, $file, $storedFileNames, $component, 'resume')),
                            ])->columns(1),
                    ])->columnSpan(1),
                ])->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['jobPosting', 'currentStage']))
            ->columns([
                Tables\Columns\TextColumn::make('full_name')
                    ->label(__('rekrutmen::filament/resources/job-application.table.columns.full_name'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('jobPosting.title')
                    ->label(__('rekrutmen::filament/resources/job-application.table.columns.job_posting'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->label(__('rekrutmen::filament/resources/job-application.table.columns.email'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('whatsapp_number')
                    ->label(__('rekrutmen::filament/resources/job-application.table.columns.whatsapp_number'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('active_phone')
                    ->label(__('rekrutmen::filament/resources/job-application.table.columns.active_phone'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('currentStage.name')
                    ->label(__('rekrutmen::filament/resources/job-application.table.columns.current_stage'))
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('rekrutmen::filament/resources/job-application.table.columns.status'))
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('job_posting_id')
                    ->relationship('jobPosting', 'title')
                    ->label(__('rekrutmen::filament/resources/job-application.table.filters.job_posting_id'))
                    ->searchable(),
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('rekrutmen::filament/resources/job-application.table.filters.status'))
                    ->options(JobApplicationStatus::class),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('change_stage')
                    ->label(__('rekrutmen::filament/resources/job-application.table.actions.change_stage'))
                    ->icon('heroicon-o-chevron-double-right')
                    ->color('warning')
                    ->visible(fn (JobApplication $record): bool => ! $record->isTerminalStatus())
                    ->form([
                        Forms\Components\Select::make('to_stage_id')
                            ->label(__('rekrutmen::filament/resources/job-application.table.actions.to_stage_id'))
                            ->options(function (JobApplication $record) {
                                if (! $record->jobPosting || ! $record->jobPosting->rekrutmen_pipeline_id) {
                                    return [];
                                }

                                return RekrutmenStage::where('rekrutmen_pipeline_id', $record->jobPosting->rekrutmen_pipeline_id)
                                    ->orderBy('order_column')
                                    ->pluck('name', 'id')
                                    ->toArray();
                            })
                            ->required(),
                        Forms\Components\Textarea::make('notes')
                            ->label(__('rekrutmen::filament/resources/job-application.table.actions.notes'))
                            ->maxLength(65535),
                    ])
                    ->action(function (JobApplication $record, array $data): void {
                        $record->transitionToStage(
                            (int) $data['to_stage_id'],
                            $data['notes'] ?? null,
                            auth()->id(),
                        );

                        Notification::make()
                            ->title(__('rekrutmen::filament/resources/job-application.notifications.stage_changed'))
                            ->success()
                            ->send();
                    }),
                Action::make('mark_hired')
                    ->label(__('rekrutmen::filament/resources/job-application.table.actions.mark_hired'))
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (JobApplication $record): bool => $record->status === JobApplicationStatus::IN_PROGRESS)
                    ->form([
                        Forms\Components\Textarea::make('notes')
                            ->label(__('rekrutmen::filament/resources/job-application.table.actions.notes'))
                            ->required()
                            ->maxLength(65535),
                    ])
                    ->action(function (JobApplication $record, array $data): void {
                        $record->markAsHired(
                            $data['notes'] ?? null,
                            auth()->id(),
                        );

                        Notification::make()
                            ->title(__('rekrutmen::filament/resources/job-application.notifications.marked_hired'))
                            ->success()
                            ->send();
                    }),
                Action::make('mark_rejected')
                    ->label(__('rekrutmen::filament/resources/job-application.table.actions.mark_rejected'))
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (JobApplication $record): bool => $record->status === JobApplicationStatus::IN_PROGRESS)
                    ->form([
                        Forms\Components\Textarea::make('notes')
                            ->label(__('rekrutmen::filament/resources/job-application.table.actions.notes'))
                            ->required()
                            ->maxLength(65535),
                    ])
                    ->action(function (JobApplication $record, array $data): void {
                        $record->markAsRejected(
                            $data['notes'] ?? null,
                            auth()->id(),
                        );

                        Notification::make()
                            ->title(__('rekrutmen::filament/resources/job-application.notifications.marked_rejected'))
                            ->success()
                            ->send();
                    }),
                Action::make('download_resume')
                    ->label(__('rekrutmen::filament/resources/job-application.table.actions.download_resume'))
                    ->icon('heroicon-o-document-arrow-down')
                    ->url(fn (JobApplication $record) => self::resolveAttachmentDownloadUrl($record, 'resume'))
                    ->openUrlInNewTab()
                    ->visible(fn (JobApplication $record) => filled($record->resume_path)),
                Action::make('download_photo')
                    ->label(__('rekrutmen::filament/resources/job-application.table.actions.download_photo'))
                    ->icon('heroicon-o-photo')
                    ->url(fn (JobApplication $record) => self::resolveAttachmentDownloadUrl($record, 'photo'))
                    ->openUrlInNewTab()
                    ->visible(fn (JobApplication $record) => filled($record->photo_path)),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            HistoriesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListJobApplications::route('/'),
            'create' => Pages\CreateJobApplication::route('/create'),
            'edit'   => Pages\EditJobApplication::route('/{record}/edit'),
            'board'  => Pages\PipelineBoard::route('/board'),
        ];
    }

    private static function resolveAttachmentDownloadUrl(JobApplication $record, string $attachment): ?string
    {
        if (! auth()->user() || blank($record->resolveAttachmentPath($attachment))) {
            return null;
        }

        return URL::temporarySignedRoute(
            'rekrutmen.job-applications.attachments.download',
            now()->addMinutes(60),
            [
                'jobApplication' => $record,
                'attachment'     => $attachment,
            ],
        );
    }

    private static function resolveUploadedFileMetadata(?JobApplication $record, string $file, string|array|null $storedFileNames, mixed $component, string $attachment): ?array
    {
        if (! $record?->getKey()) {
            return null;
        }

        $storage = $component->getDisk();
        $shouldFetchFileInformation = $component->shouldFetchFileInformation();

        if ($shouldFetchFileInformation) {
            try {
                if (! $storage->exists($file)) {
                    return null;
                }
            } catch (UnableToCheckFileExistence) {
                return null;
            }
        }

        return [
            'name' => ($component->isMultiple() ? ($storedFileNames[$file] ?? null) : $storedFileNames) ?? basename($file),
            'size' => $shouldFetchFileInformation ? $storage->size($file) : 0,
            'type' => $shouldFetchFileInformation ? $storage->mimeType($file) : null,
            'url'  => self::resolveAttachmentDownloadUrl($record, $attachment),
        ];
    }
}
