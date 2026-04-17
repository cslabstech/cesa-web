<?php

namespace Cesa\Rekrutmen\Filament\Resources;

use Cesa\Rekrutmen\Filament\Resources\JobPostingResource\Pages;
use Cesa\Rekrutmen\Models\JobPosting;
use Cesa\Rekrutmen\Models\RequestManPower;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class JobPostingResource extends Resource
{
    protected static ?string $model = JobPosting::class;

    protected static \BackedEnum|string|null $navigationIcon = null;

    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): string
    {
        return __('admin.navigation.rekrutmen');
    }

    public static function getNavigationLabel(): string
    {
        return __('rekrutmen::filament/resources/job-posting.navigation.label');
    }

    public static function getModelLabel(): string
    {
        return __('rekrutmen::filament/resources/job-posting.model.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('rekrutmen::filament/resources/job-posting.model.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Grid::make(3)->schema([
                    Group::make([
                        Section::make(__('rekrutmen::filament/resources/job-posting.form.sections.job_information'))
                            ->schema([
                                Forms\Components\TextInput::make('title')
                                    ->label(__('rekrutmen::filament/resources/job-posting.form.fields.title'))
                                    ->required()
                                    ->maxLength(255)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn (string $operation, $state, Set $set) => $operation === 'create' ? $set('slug', Str::slug($state)) : null),
                                Forms\Components\TextInput::make('slug')
                                    ->label(__('rekrutmen::filament/resources/job-posting.form.fields.slug'))
                                    ->required()
                                    ->maxLength(255)
                                    ->unique(JobPosting::class, 'slug', ignoreRecord: true),
                                Forms\Components\TextInput::make('location')
                                    ->label(__('rekrutmen::filament/resources/job-posting.form.fields.location'))
                                    ->maxLength(255),
                            ])->columns(2),

                        Section::make(__('rekrutmen::filament/resources/job-posting.form.sections.details'))
                            ->schema([
                                Forms\Components\Textarea::make('description')
                                    ->label(__('rekrutmen::filament/resources/job-posting.form.fields.description'))
                                    ->required()
                                    ->rows(5)
                                    ->columnSpanFull(),
                                Forms\Components\Textarea::make('requirements')
                                    ->label(__('rekrutmen::filament/resources/job-posting.form.fields.requirements'))
                                    ->required()
                                    ->rows(5)
                                    ->columnSpanFull(),
                            ]),
                    ])->columnSpan(2),

                    Group::make([
                        Section::make(__('rekrutmen::filament/resources/job-posting.form.sections.settings'))
                            ->schema([
                                Forms\Components\Select::make('request_man_power_id')
                                    ->relationship('requestManPower', 'posisi_dibutuhkan')
                                    ->searchable()
                                    ->preload()
                                    ->nullable()
                                    ->live()
                                    ->afterStateUpdated(function (mixed $state, Set $set, Get $get, string $operation): void {
                                        if (! $state || $operation !== 'create') {
                                            return;
                                        }

                                        $requestManPower = RequestManPower::query()->find($state);

                                        if (! $requestManPower) {
                                            return;
                                        }

                                        foreach (self::resolveAutofillDataFromRequestManPower($requestManPower) as $field => $value) {
                                            if ($value === null && blank($get($field))) {
                                                continue;
                                            }

                                            $set($field, $value);
                                        }
                                    })
                                    ->label(__('rekrutmen::filament/resources/job-posting.form.fields.request_man_power_id')),
                                Forms\Components\Select::make('rekrutmen_pipeline_id')
                                    ->relationship('rekrutmenPipeline', 'name')
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->label(__('rekrutmen::filament/resources/job-posting.form.fields.rekrutmen_pipeline_id')),
                                Forms\Components\DatePicker::make('closing_date')
                                    ->label(__('rekrutmen::filament/resources/job-posting.form.fields.closing_date')),
                                Forms\Components\Toggle::make('is_published')
                                    ->label(__('rekrutmen::filament/resources/job-posting.form.fields.is_published'))
                                    ->default(false),
                                Forms\Components\FileUpload::make('thumbnail_path')
                                    ->label(__('rekrutmen::filament/resources/job-posting.form.fields.thumbnail_path'))
                                    ->disk(JobPosting::thumbnailDisk())
                                    ->directory(JobPosting::THUMBNAIL_DIRECTORY)
                                    ->image()
                                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                    ->maxSize(5120)
                                    ->visibility('public')
                                    ->imagePreviewHeight('160')
                                    ->openable(),
                            ])->columns(1),
                    ])->columnSpan(1),
                ])->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['requestManPower', 'rekrutmenPipeline'])->withCount('applications'))
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label(__('rekrutmen::filament/resources/job-posting.table.columns.title'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('rekrutmenPipeline.name')
                    ->label(__('rekrutmen::filament/resources/job-posting.table.columns.rekrutmen_pipeline'))
                    ->badge()
                    ->color('gray')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('requestManPower.posisi_dibutuhkan')
                    ->label(__('rekrutmen::filament/resources/job-posting.table.columns.request_man_power'))
                    ->placeholder(__('rekrutmen::filament/resources/job-posting.table.placeholders.request_man_power'))
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\ImageColumn::make('thumbnail_path')
                    ->label(__('rekrutmen::filament/resources/job-posting.table.columns.thumbnail_path'))
                    ->disk(JobPosting::thumbnailDisk())
                    ->circular(),
                Tables\Columns\TextColumn::make('location')
                    ->label(__('rekrutmen::filament/resources/job-posting.table.columns.location'))
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('applications_count')
                    ->label(__('rekrutmen::filament/resources/job-posting.table.columns.applications_count'))
                    ->numeric()
                    ->badge()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_published')
                    ->label(__('rekrutmen::filament/resources/job-posting.table.columns.is_published'))
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('closing_date')
                    ->label(__('rekrutmen::filament/resources/job-posting.table.columns.closing_date'))
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_published')
                    ->label(__('rekrutmen::filament/resources/job-posting.table.filters.is_published')),
                Tables\Filters\SelectFilter::make('rekrutmen_pipeline_id')
                    ->relationship('rekrutmenPipeline', 'name')
                    ->label(__('rekrutmen::filament/resources/job-posting.table.filters.rekrutmen_pipeline_id'))
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('request_man_power_id')
                    ->relationship('requestManPower', 'posisi_dibutuhkan')
                    ->label(__('rekrutmen::filament/resources/job-posting.table.filters.request_man_power_id'))
                    ->searchable()
                    ->preload(),
                Tables\Filters\Filter::make('availability')
                    ->label(__('rekrutmen::filament/resources/job-posting.table.filters.availability'))
                    ->form([
                        Forms\Components\Select::make('state')
                            ->label(__('rekrutmen::filament/resources/job-posting.table.filters.availability'))
                            ->options([
                                'open'    => __('rekrutmen::filament/resources/job-posting.table.filter_options.availability.open'),
                                'expired' => __('rekrutmen::filament/resources/job-posting.table.filter_options.availability.expired'),
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['state'] ?? null) {
                            'open' => $query->where(function (Builder $query): void {
                                $query->whereNull('closing_date')
                                    ->orWhereDate('closing_date', '>=', today());
                            }),
                            'expired' => $query->whereDate('closing_date', '<', today()),
                            default   => $query,
                        };
                    }),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('open_pipeline')
                    ->label(__('rekrutmen::filament/resources/job-posting.table.actions.open_pipeline'))
                    ->icon('heroicon-o-view-columns')
                    ->color('gray')
                    ->url(fn (JobPosting $record): string => JobApplicationResource::getUrl('board', ['job_posting_id' => $record->id])),
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListJobPostings::route('/'),
            'create' => Pages\CreateJobPosting::route('/create'),
            'edit'   => Pages\EditJobPosting::route('/{record}/edit'),
        ];
    }

    /**
     * @return array{
     *     title: string,
     *     slug: string,
     *     location: ?string,
     *     description: ?string,
     *     requirements: ?string,
     *     closing_date: ?string,
     *     rekrutmen_pipeline_id: ?int
     * }
     */
    public static function resolveAutofillDataFromRequestManPower(RequestManPower $requestManPower): array
    {
        $title = trim(implode(' ', array_filter([
            $requestManPower->posisi_dibutuhkan,
            $requestManPower->lokasi_penempatan,
        ])));

        return [
            'title'                 => $title,
            'slug'                  => Str::slug($title),
            'location'              => $requestManPower->lokasi_penempatan,
            'description'           => $requestManPower->job_description,
            'requirements'          => $requestManPower->requirements_kualifikasi,
            'closing_date'          => $requestManPower->estimasi_tanggal_join?->toDateString(),
            'rekrutmen_pipeline_id' => $requestManPower->jobPosting?->rekrutmen_pipeline_id,
        ];
    }
}
