<?php

namespace Cesa\Rekrutmen\Filament\Resources\JobApplicationResource\Pages;

use Cesa\Rekrutmen\Enums\ActivityEntryResult;
use Cesa\Rekrutmen\Enums\JobApplicationGender;
use Cesa\Rekrutmen\Enums\JobApplicationStatus;
use Cesa\Rekrutmen\Filament\Resources\JobApplicationResource;
use Cesa\Rekrutmen\Models\JobApplication;
use Cesa\Rekrutmen\Models\JobPosting;
use Cesa\Rekrutmen\Models\RekrutmenStage;
use Filament\Actions\Action;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Contracts\HasForms;
use Filament\Infolists\Components\ViewEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
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

    protected string $view = 'rekrutmen::filament.pages.pipeline-board';

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
        return null;
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
                    ->orderByRaw('CASE WHEN status IN (?, ?, ?) THEN 1 ELSE 0 END', [
                        JobApplicationStatus::REJECTED->value,
                        JobApplicationStatus::WITHDRAWN->value,
                        JobApplicationStatus::HIRED->value,
                    ])
            )
            ->recordTitleAttribute('full_name')
            ->columnIdentifier('current_stage_id')
            ->positionIdentifier('position')
            ->cardSchema(fn (Schema $schema): Schema => $schema->components([
                ViewEntry::make('board_summary')
                    ->hiddenLabel()
                    ->view('rekrutmen::filament.infolists.job-application-board-summary')
                    ->state(fn (JobApplication $record): array => [
                        'avatar_url'      => $this->resolveBoardPhotoUrl($record),
                        'avatar_initials' => $this->resolveBoardInitials($record->full_name),
                        'age'             => $this->resolveBoardAge($record),
                        'gender'          => $this->resolveBoardGenderLabel($record->gender),
                        'last_updated'    => $this->resolveBoardUpdatedAtLabel($record),
                        'source'          => $this->resolveBoardSourceLabel($record->source),
                        'status'          => $this->resolveBoardStatusLabel($record->status),
                        'status_color'    => $record->status?->getColor(),
                        'status_context'  => $record->isTerminalStatus() ? $this->resolveBoardStatusContext($record) : null,
                        'status_icon'     => $this->resolveBoardStatusIcon($record->status),
                    ]),
            ]))
            ->recordActions([
                Action::make('record_activity')
                    ->label(__('rekrutmen::filament/resources/activity-log.navigation.label'))
                    ->icon('heroicon-o-clipboard-document-list')
                    ->color('gray')
                    ->visible(fn (JobApplication $record): bool => $record->status === JobApplicationStatus::IN_PROGRESS
                        && filled($record->currentStage?->name))
                    ->form([
                        Placeholder::make('candidate_name')
                            ->label(__('rekrutmen::filament/resources/activity-log.form.fields.candidate'))
                            ->content(fn (JobApplication $record): string => $record->full_name),
                        Placeholder::make('current_stage')
                            ->label(__('rekrutmen::filament/resources/activity-log.form.fields.stage_id'))
                            ->content(fn (JobApplication $record): string => $record->currentStage?->name ?? '-'),
                        DatePicker::make('activity_date')
                            ->label(__('rekrutmen::filament/resources/activity-log.form.fields.activity_date'))
                            ->required()
                            ->default(now()->toDateString()),
                        Select::make('result')
                            ->label(__('rekrutmen::filament/resources/activity-log.form.fields.result'))
                            ->options(ActivityEntryResult::activityOptions())
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
                Action::make('view_candidate')
                    ->label(__('rekrutmen::filament/resources/job-application.table.actions.view'))
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->url(fn (JobApplication $record): string => JobApplicationResource::getUrl('view', ['record' => $record]))
                    ->openUrlInNewTab(),
                Action::make('mark_hired')
                    ->label(__('rekrutmen::filament/resources/job-application.table.actions.mark_hired'))
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (JobApplication $record): bool => $record->canMarkAsHired())
                    ->form([
                        DatePicker::make('activity_date')
                            ->label(__('rekrutmen::filament/resources/activity-log.form.fields.activity_date'))
                            ->required()
                            ->default(now()->toDateString())
                            ->maxDate(today()),
                        Textarea::make('notes')
                            ->label(__('rekrutmen::filament/resources/job-application.table.actions.notes'))
                            ->required()
                            ->maxLength(65535),
                    ])
                    ->action(function (JobApplication $record, array $data): void {
                        $record->markAsHired($data['notes'] ?? null, auth()->id(), (string) $data['activity_date']);

                        Notification::make()
                            ->title(__('rekrutmen::filament/resources/job-application.notifications.marked_hired'))
                            ->success()
                            ->send();

                        $this->redirect(static::getUrl(['job_posting_id' => $this->activeJobPostingId]));
                    }),
                Action::make('mark_withdrawn')
                    ->label(__('rekrutmen::filament/resources/job-application.table.actions.mark_withdrawn'))
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('warning')
                    ->visible(fn (JobApplication $record): bool => $record->canMarkAsWithdrawn())
                    ->form([
                        DatePicker::make('activity_date')
                            ->label(__('rekrutmen::filament/resources/activity-log.form.fields.activity_date'))
                            ->required()
                            ->default(now()->toDateString())
                            ->maxDate(today()),
                        Textarea::make('notes')
                            ->label(__('rekrutmen::filament/resources/job-application.table.actions.notes'))
                            ->required()
                            ->maxLength(65535),
                    ])
                    ->action(function (JobApplication $record, array $data): void {
                        $record->markAsWithdrawn($data['notes'] ?? null, auth()->id(), (string) $data['activity_date']);

                        Notification::make()
                            ->title(__('rekrutmen::filament/resources/job-application.notifications.marked_withdrawn'))
                            ->success()
                            ->send();

                        $this->redirect(static::getUrl(['job_posting_id' => $this->activeJobPostingId]));
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
        $targetStageName = $this->resolveStageName($targetStageId);

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

        if (! $this->canAdvanceToNextStage($application, $targetStageId)) {
            Notification::make()
                ->title(__('rekrutmen::filament/resources/job-application.workflow_errors.drag_only_next_stage'))
                ->warning()
                ->send();

            return;
        }

        try {
            JobApplication::recordBatchActivity(
                (int) $application->job_posting_id,
                (int) $fromStageId,
                Carbon::now()->toDateString(),
                [[
                    'job_application_id' => $application->id,
                    'result'             => 'passed',
                    'notes'              => __('rekrutmen::filament/resources/job-application.workflow_notes.drag_passed', [
                        'stage' => $targetStageName,
                    ]),
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

        $notification = Notification::make()
            ->success();

        if ($this->isHiredStageName($targetStageName)) {
            $notification
                ->title(__('rekrutmen::filament/resources/job-application.notifications.hired_stage_reached'))
                ->body(__('rekrutmen::filament/resources/job-application.notifications.hired_stage_reached_help'));
        } else {
            $notification
                ->title(__('rekrutmen::filament/resources/job-application.notifications.drag_passed', [
                    'stage' => $targetStageName,
                ]));
        }

        $notification->send();
    }

    protected function canAdvanceToNextStage(JobApplication $application, int $targetStageId): bool
    {
        $currentStage = RekrutmenStage::query()
            ->whereKey($application->current_stage_id)
            ->where('rekrutmen_pipeline_id', $application->jobPosting?->rekrutmen_pipeline_id)
            ->first();

        if (! $currentStage) {
            return false;
        }

        $nextStageId = RekrutmenStage::query()
            ->where('rekrutmen_pipeline_id', $currentStage->rekrutmen_pipeline_id)
            ->where('order_column', '>', $currentStage->order_column)
            ->orderBy('order_column')
            ->value('id');

        return $nextStageId === $targetStageId;
    }

    protected function resolveStageName(int $stageId): string
    {
        return RekrutmenStage::query()
            ->whereKey($stageId)
            ->value('name')
            ?? __('rekrutmen::filament/resources/job-application.board.card.current_stage_fallback');
    }

    protected function isHiredStageName(string $stageName): bool
    {
        return Str::lower(trim($stageName)) === 'hired';
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

    protected function resolveBoardAge(JobApplication $record): ?string
    {
        if (! $record->birth_date) {
            return null;
        }

        return $record->birth_date->age.' Thn';
    }

    protected function resolveBoardSourceLabel(?string $source): ?string
    {
        if (blank($source)) {
            return null;
        }

        return match (Str::lower($source)) {
            'jobstreet' => 'JobStreet',
            'linkedin'  => 'LinkedIn',
            'github'    => 'GitHub',
            'walk-in'   => 'Walk-In',
            default     => Str::of($source)
                ->replace(['-', '_'], ' ')
                ->headline()
                ->toString(),
        };
    }

    protected function resolveBoardGenderLabel(JobApplicationGender|string|null $gender): ?string
    {
        if ($gender instanceof JobApplicationGender) {
            return $gender->getLabel();
        }

        if (is_string($gender) && $gender !== '') {
            return JobApplicationGender::tryFrom($gender)?->getLabel() ?? Str::headline($gender);
        }

        return null;
    }

    protected function resolveBoardPhotoUrl(JobApplication $record): ?string
    {
        if (blank($record->resolveAttachmentPath('photo'))) {
            return null;
        }

        if (! auth()->user()) {
            return null;
        }

        return URL::signedRoute('rekrutmen.job-applications.attachments.download', [
            'jobApplication' => $record,
            'attachment'     => 'photo',
        ]);
    }

    protected function resolveBoardUpdatedAtLabel(JobApplication $record): ?string
    {
        if (! $record->updated_at) {
            return null;
        }

        return __('rekrutmen::filament/resources/job-application.board.card.updated_at', [
            'time' => $record->updated_at
                ->locale(app()->getLocale())
                ->translatedFormat('d M Y, H:i'),
        ]);
    }

    protected function resolveBoardInitials(?string $fullName): string
    {
        $segments = Str::of((string) $fullName)
            ->trim()
            ->explode(' ')
            ->filter()
            ->take(2);

        if ($segments->isEmpty()) {
            return 'NA';
        }

        return $segments
            ->map(fn (string $segment): string => Str::upper(Str::substr($segment, 0, 1)))
            ->implode('');
    }

    protected function resolveBoardStatusIcon(JobApplicationStatus|string|null $status): string
    {
        $resolvedStatus = $status instanceof JobApplicationStatus
            ? $status
            : JobApplicationStatus::tryFrom((string) $status);

        return match ($resolvedStatus) {
            JobApplicationStatus::HIRED     => 'heroicon-m-check-badge',
            JobApplicationStatus::REJECTED  => 'heroicon-m-x-circle',
            JobApplicationStatus::WITHDRAWN => 'heroicon-m-arrow-uturn-left',
            default                         => 'heroicon-m-clock',
        };
    }
}
