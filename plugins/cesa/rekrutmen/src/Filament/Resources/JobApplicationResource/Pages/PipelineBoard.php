<?php

namespace Cesa\Rekrutmen\Filament\Resources\JobApplicationResource\Pages;

use Cesa\Rekrutmen\Enums\ActivityEntryResult;
use Cesa\Rekrutmen\Enums\JobApplicationStatus;
use Cesa\Rekrutmen\Filament\Resources\JobApplicationResource;
use Cesa\Rekrutmen\Models\JobApplication;
use Cesa\Rekrutmen\Models\JobPosting;
use Cesa\Rekrutmen\Models\RekrutmenStage;
use Filament\Actions\Action;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Contracts\HasForms;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;
use Relaticle\Flowforge\Board;
use Relaticle\Flowforge\Column;
use Relaticle\Flowforge\Concerns\BaseBoard;
use Relaticle\Flowforge\Contracts\HasBoard;

class PipelineBoard extends Page implements HasActions, HasBoard, HasForms
{
    use BaseBoard {
        moveCard as protected baseMoveCard;
    }

    protected static string $resource = JobApplicationResource::class;

    protected static string|null|\BackedEnum $navigationIcon = 'heroicon-o-view-columns';

    protected static ?string $navigationLabel = null;

    protected string $view = 'flowforge::filament.pages.board-page';

    public ?int $activeJobPostingId = null;

    public function mount(): void
    {
        if ($this->activeJobPostingId) {
            return;
        }

        $latestJobPosting = JobPosting::query()->latest()->first();

        if ($latestJobPosting) {
            $this->activeJobPostingId = $latestJobPosting->id;
        }
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 1;
    }

    public static function getNavigationLabel(): string
    {
        return __('rekrutmen::filament/resources/job-application.board.navigation_label');
    }

    public function getHeading(): string
    {
        $jobPosting = $this->resolveActiveJobPosting();

        if (! $jobPosting) {
            return __('rekrutmen::filament/resources/job-application.board.heading');
        }

        return __('rekrutmen::filament/resources/job-application.board.heading_with_job', [
            'job' => $jobPosting->title,
        ]);
    }

    public function getSubheading(): ?string
    {
        $jobPosting = $this->resolveActiveJobPosting();

        if (! $jobPosting) {
            return __('rekrutmen::filament/resources/job-application.board.subheading');
        }

        return __('rekrutmen::filament/resources/job-application.board.subheading_with_job', [
            'job' => $jobPosting->title,
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back_to_list')
                ->label(__('rekrutmen::filament/resources/job-application.board.back_to_list'))
                ->icon('heroicon-o-table-cells')
                ->color('gray')
                ->url(JobApplicationResource::getUrl('index')),
            Action::make('select_job_posting')
                ->label(__('rekrutmen::filament/resources/job-application.board.select_job_posting'))
                ->icon('heroicon-o-funnel')
                ->color('gray')
                ->form([
                    Select::make('job_posting_id')
                        ->label(__('rekrutmen::filament/resources/job-application.form.fields.job_posting_id'))
                        ->options(JobPosting::query()->orderBy('title')->pluck('title', 'id'))
                        ->required()
                        ->default($this->activeJobPostingId)
                        ->searchable(),
                ])
                ->action(function (array $data) {
                    $this->activeJobPostingId = $data['job_posting_id'];
                    $this->redirect(static::getUrl(['job_posting_id' => $this->activeJobPostingId]));
                }),
        ];
    }

    protected function queryString(): array
    {
        return [
            'activeJobPostingId' => ['as' => 'job_posting_id', 'except' => ''],
        ];
    }

    public function board(Board $board): Board
    {
        return $board
            ->query(
                JobApplication::query()
                    ->with('currentStage')
                    ->when($this->activeJobPostingId, fn (Builder $query) => $query->where('job_posting_id', $this->activeJobPostingId))
            )
            ->recordTitleAttribute('full_name')
            ->columnIdentifier('current_stage_id')
            ->positionIdentifier('position')
            ->cardSchema(fn (Schema $schema): Schema => $schema->components([
                TextEntry::make('email')
                    ->hiddenLabel()
                    ->color('gray'),
                TextEntry::make('status')
                    ->hiddenLabel()
                    ->badge()
                    ->formatStateUsing(fn (JobApplicationStatus|string|null $state): string => $this->resolveBoardStatusLabel($state))
                    ->color(fn (JobApplication $record): string|array|null => $record->status?->getColor())
                    ->visible(fn (JobApplication $record): bool => $record->isTerminalStatus()),
                TextEntry::make('board_status_context')
                    ->hiddenLabel()
                    ->state(fn (JobApplication $record): ?string => $this->resolveBoardStatusContext($record))
                    ->visible(fn (JobApplication $record): bool => $record->isTerminalStatus()),
            ]))
            ->recordActions([
                Action::make('record_activity')
                    ->label(__('rekrutmen::filament/resources/activity-log.navigation.label'))
                    ->icon('heroicon-o-clipboard-document-list')
                    ->color('gray')
                    ->visible(fn (JobApplication $record): bool => $record->status === JobApplicationStatus::IN_PROGRESS
                        && filled($record->currentStage?->name))
                    ->form([
                        DatePicker::make('activity_date')
                            ->label(__('rekrutmen::filament/resources/activity-log.form.fields.activity_date'))
                            ->required()
                            ->default(now()->toDateString()),
                        Select::make('result')
                            ->label(__('rekrutmen::filament/resources/activity-log.form.fields.result'))
                            ->options(ActivityEntryResult::class)
                            ->required()
                            ->default(ActivityEntryResult::PENDING->value)
                            ->live(),
                        Textarea::make('notes')
                            ->label(__('rekrutmen::filament/resources/activity-log.form.fields.notes'))
                            ->maxLength(65535)
                            ->required(fn (Get $get): bool => $get('result') === ActivityEntryResult::FAILED->value)
                            ->helperText(__('rekrutmen::filament/resources/activity-log.form.helpers.failed_requires_notes')),
                    ])
                    ->action(function (JobApplication $record, array $data): void {
                        try {
                            JobApplication::recordBatchActivity(
                                (int) $record->job_posting_id,
                                (int) $record->current_stage_id,
                                (string) $data['activity_date'],
                                [[
                                    'job_application_id' => $record->id,
                                    'result'             => $data['result'] ?? ActivityEntryResult::PENDING->value,
                                    'notes'              => $data['notes'] ?? null,
                                ]],
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
                    }),
                EditAction::make()
                    ->url(fn (JobApplication $record): string => JobApplicationResource::getUrl('edit', ['record' => $record])),
                Action::make('mark_hired')
                    ->label(__('rekrutmen::filament/resources/job-application.table.actions.mark_hired'))
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (JobApplication $record): bool => $record->status === JobApplicationStatus::IN_PROGRESS)
                    ->form([
                        Textarea::make('notes')
                            ->label(__('rekrutmen::filament/resources/job-application.table.actions.notes'))
                            ->required()
                            ->maxLength(65535),
                    ])
                    ->action(function (JobApplication $record, array $data): void {
                        $record->markAsHired($data['notes'] ?? null, auth()->id());

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
                        Textarea::make('notes')
                            ->label(__('rekrutmen::filament/resources/job-application.table.actions.notes'))
                            ->required()
                            ->maxLength(65535),
                    ])
                    ->action(function (JobApplication $record, array $data): void {
                        $record->markAsRejected($data['notes'] ?? null, auth()->id());

                        Notification::make()
                            ->title(__('rekrutmen::filament/resources/job-application.notifications.marked_rejected'))
                            ->success()
                            ->send();
                    }),
            ])
            ->columns($this->getDynamicColumns());
    }

    public function moveCard(
        string $cardId,
        string $targetColumnId,
        ?string $afterCardId = null,
        ?string $beforeCardId = null
    ): void {
        $application = JobApplication::query()->findOrFail($cardId);

        if ($application->isTerminalStatus()) {
            Notification::make()
                ->title(__('rekrutmen::filament/resources/job-application.workflow_errors.terminal_stage_locked'))
                ->danger()
                ->send();

            return;
        }

        $targetStageId = (int) $targetColumnId;

        if (! $application->stageBelongsToCurrentPipeline($targetStageId)) {
            Notification::make()
                ->title(__('rekrutmen::filament/resources/job-application.workflow_errors.invalid_stage'))
                ->danger()
                ->send();

            return;
        }

        $fromStageId = $application->current_stage_id;

        if ($fromStageId === $targetStageId) {
            try {
                $this->baseMoveCard($cardId, $targetColumnId, $afterCardId, $beforeCardId);
            } catch (InvalidArgumentException $exception) {
                Notification::make()
                    ->title($exception->getMessage())
                    ->danger()
                    ->send();
            }

            return;
        }

        Notification::make()
            ->title(__('rekrutmen::filament/resources/job-application.workflow_errors.cross_stage_requires_activity'))
            ->warning()
            ->send();
    }

    protected function getDynamicColumns(): array
    {
        if (! $this->activeJobPostingId) {
            return [
                Column::make('empty')->label(__('rekrutmen::filament/resources/job-application.board.no_job_selected')),
            ];
        }

        $jobPosting = $this->resolveActiveJobPosting();

        if (! $jobPosting || ! $jobPosting->rekrutmen_pipeline_id) {
            return [
                Column::make('empty')->label(__('rekrutmen::filament/resources/job-application.board.no_pipeline')),
            ];
        }

        $stages = RekrutmenStage::where('rekrutmen_pipeline_id', $jobPosting->rekrutmen_pipeline_id)
            ->orderBy('order_column')
            ->get();

        if ($stages->isEmpty()) {
            return [
                Column::make('empty')->label(__('rekrutmen::filament/resources/job-application.board.no_stages')),
            ];
        }

        return $stages->map(function ($stage) {
            return Column::make((string) $stage->id)
                ->label($stage->name);
        })->toArray();
    }

    protected function resolveActiveJobPosting(): ?JobPosting
    {
        if (! $this->activeJobPostingId) {
            return null;
        }

        return JobPosting::query()->find($this->activeJobPostingId);
    }

    protected function resolveBoardStatusLabel(JobApplicationStatus|string|null $status): string
    {
        if ($status instanceof JobApplicationStatus) {
            return $status->getLabel() ?? $status->value;
        }

        if (is_string($status) && $status !== '') {
            return JobApplicationStatus::tryFrom($status)?->getLabel() ?? $status;
        }

        return '';
    }

    protected function resolveBoardStatusContext(JobApplication $record): ?string
    {
        $stageName = $record->currentStage?->name
            ?? __('rekrutmen::filament/resources/job-application.board.card.current_stage_fallback');

        return match ($record->status) {
            JobApplicationStatus::HIRED => __('rekrutmen::filament/resources/job-application.board.card.status_context.hired', [
                'stage' => $stageName,
            ]),
            JobApplicationStatus::REJECTED => __('rekrutmen::filament/resources/job-application.board.card.status_context.rejected', [
                'stage' => $stageName,
            ]),
            JobApplicationStatus::WITHDRAWN => __('rekrutmen::filament/resources/job-application.board.card.status_context.withdrawn', [
                'stage' => $stageName,
            ]),
            default => null,
        };
    }
}
