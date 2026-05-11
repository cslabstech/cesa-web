<?php

namespace Cesa\Rekrutmen\Filament\Resources\ActivityLogResource\Pages;

use Cesa\Rekrutmen\Enums\ActivityEntryResult;
use Cesa\Rekrutmen\Enums\JobApplicationStatus;
use Cesa\Rekrutmen\Filament\Resources\ActivityLogResource;
use Cesa\Rekrutmen\Models\JobApplication;
use Cesa\Rekrutmen\Models\JobPosting;
use Cesa\Rekrutmen\Models\RekrutmenStage;
use Filament\Forms;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use InvalidArgumentException;

class CreateActivityLog extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = ActivityLogResource::class;

    protected string $view = 'rekrutmen::filament.pages.create-activity-log';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'activity_date' => now()->toDateString(),
            'candidates'    => [[]],
        ]);
    }

    public function getSubheading(): ?string
    {
        return __('rekrutmen::filament/resources/activity-log.form.helpers.create_subheading');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make(__('rekrutmen::filament/resources/activity-log.form.sections.activity_details'))
                    ->schema([
                        Forms\Components\Select::make('job_posting_id')
                            ->label(__('rekrutmen::filament/resources/activity-log.form.fields.job_posting_id'))
                            ->options(fn (): array => $this->jobPostingOptions())
                            ->required()
                            ->searchable()
                            ->live()
                            ->afterStateUpdated(function (Set $set): void {
                                $set('stage_id', null);
                                $set('candidates', [[]]);
                            }),
                        Forms\Components\Select::make('stage_id')
                            ->label(__('rekrutmen::filament/resources/activity-log.form.fields.stage_id'))
                            ->options(fn (Get $get): array => $this->stageOptions($get('job_posting_id')))
                            ->required()
                            ->searchable()
                            ->live()
                            ->afterStateUpdated(fn (Set $set): mixed => $set('candidates', [[]])),
                        Forms\Components\DatePicker::make('activity_date')
                            ->label(__('rekrutmen::filament/resources/activity-log.form.fields.activity_date'))
                            ->required()
                            ->default(now()->toDateString())
                            ->maxDate(today()),
                        Forms\Components\Placeholder::make('generated_title')
                            ->label(__('rekrutmen::filament/resources/activity-log.form.fields.generated_title'))
                            ->content(fn (Get $get): string => $this->generatedActivityTitle(
                                $get('stage_id'),
                                $get('activity_date'),
                            )),
                    ])
                    ->columns(2),
                Section::make(__('rekrutmen::filament/resources/activity-log.form.sections.candidates'))
                    ->description(__('rekrutmen::filament/resources/activity-log.form.helpers.info_note'))
                    ->schema([
                        Repeater::make('candidates')
                            ->hiddenLabel()
                            ->addActionLabel(__('rekrutmen::filament/resources/activity-log.form.actions.add_candidate'))
                            ->minItems(1)
                            ->schema([
                                Forms\Components\Select::make('job_application_id')
                                    ->label(__('rekrutmen::filament/resources/activity-log.form.fields.candidate'))
                                    ->options(fn (Get $get): array => $this->candidateOptions(
                                        $get('../../job_posting_id'),
                                        $get('../../stage_id'),
                                    ))
                                    ->required()
                                    ->searchable(),
                                Forms\Components\Select::make('result')
                                    ->label(__('rekrutmen::filament/resources/activity-log.form.fields.result'))
                                    ->options(ActivityEntryResult::activityOptions())
                                    ->default(ActivityEntryResult::PENDING->value)
                                    ->required()
                                    ->live(),
                                Forms\Components\Textarea::make('notes')
                                    ->label(__('rekrutmen::filament/resources/activity-log.form.fields.notes'))
                                    ->required(fn (Get $get): bool => $get('result') === ActivityEntryResult::FAILED->value)
                                    ->helperText(__('rekrutmen::filament/resources/activity-log.form.helpers.failed_requires_notes'))
                                    ->maxLength(65535),
                            ])
                            ->columns(3),
                    ]),
            ])
            ->statePath('data');
    }

    public function create(): void
    {
        $data = $this->form->getState();
        $entries = collect($data['candidates'] ?? [])
            ->filter(fn (array $candidate): bool => is_numeric($candidate['job_application_id'] ?? null))
            ->map(fn (array $candidate): array => [
                'job_application_id' => (int) $candidate['job_application_id'],
                'result'             => $candidate['result'] ?? ActivityEntryResult::PENDING->value,
                'notes'              => $candidate['notes'] ?? null,
            ])
            ->values()
            ->all();

        if ($entries === []) {
            Notification::make()
                ->title(__('rekrutmen::filament/resources/activity-log.notifications.no_candidates'))
                ->danger()
                ->send();

            return;
        }

        try {
            JobApplication::recordBatchActivity(
                (int) $data['job_posting_id'],
                (int) $data['stage_id'],
                (string) $data['activity_date'],
                $entries,
                auth()->id(),
            );
        } catch (InvalidArgumentException $exception) {
            Notification::make()
                ->title($exception->getMessage())
                ->danger()
                ->send();

            return;
        }

        Notification::make()
            ->title(__('rekrutmen::filament/resources/activity-log.notifications.activity_recorded'))
            ->success()
            ->send();

        $this->form->fill([
            'job_posting_id' => $data['job_posting_id'],
            'stage_id'       => $data['stage_id'],
            'activity_date'  => $data['activity_date'],
            'candidates'     => [[]],
        ]);
    }

    /**
     * @return array<int, string>
     */
    protected function jobPostingOptions(): array
    {
        return JobPosting::query()
            ->orderBy('title')
            ->pluck('title', 'id')
            ->all();
    }

    /**
     * @return array<int, string>
     */
    protected function stageOptions(mixed $jobPostingId): array
    {
        if (! is_numeric($jobPostingId)) {
            return [];
        }

        $pipelineId = JobPosting::query()
            ->whereKey((int) $jobPostingId)
            ->value('rekrutmen_pipeline_id');

        if (! is_numeric($pipelineId)) {
            return [];
        }

        return RekrutmenStage::query()
            ->where('rekrutmen_pipeline_id', (int) $pipelineId)
            ->orderBy('order_column')
            ->pluck('name', 'id')
            ->all();
    }

    /**
     * @return array<int, string>
     */
    protected function candidateOptions(mixed $jobPostingId, mixed $stageId): array
    {
        if (! is_numeric($jobPostingId) || ! is_numeric($stageId)) {
            return [];
        }

        return JobApplication::query()
            ->where('job_posting_id', (int) $jobPostingId)
            ->where('current_stage_id', (int) $stageId)
            ->where('status', JobApplicationStatus::IN_PROGRESS->value)
            ->orderBy('full_name')
            ->pluck('full_name', 'id')
            ->all();
    }

    protected function generatedActivityTitle(mixed $stageId, mixed $activityDate): string
    {
        if (! is_numeric($stageId) || blank($activityDate)) {
            return __('rekrutmen::filament/resources/activity-log.form.helpers.generated_title_placeholder');
        }

        $stageName = RekrutmenStage::query()
            ->whereKey((int) $stageId)
            ->value('name');

        if (! is_string($stageName) || $stageName === '') {
            return __('rekrutmen::filament/resources/activity-log.form.helpers.generated_title_placeholder');
        }

        return JobApplication::generateBatchActivityTitle($stageName, (string) $activityDate);
    }
}
