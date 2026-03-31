<?php

namespace Cesa\Rekrutmen\Filament\Resources\JobApplicationResource\Pages;

use Cesa\Rekrutmen\Enums\JobApplicationStatus;
use Cesa\Rekrutmen\Filament\Resources\JobApplicationResource;
use Cesa\Rekrutmen\Models\JobApplication;
use Cesa\Rekrutmen\Models\JobPosting;
use Cesa\Rekrutmen\Models\RekrutmenStage;
use Filament\Actions\Action;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;
use Livewire\Attributes\Url;
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

    protected static ?string $navigationLabel = 'Pipeline Board';

    protected string $view = 'flowforge::filament.pages.board-page';

    #[Url(as: 'filters')]
    public ?array $tableFilters = null;

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

    // We can use native Filament Header Actions to provide a selector if needed,
    // but the best way to handle dynamic filter is via table-like filters or form
    // Since Flowforge integrates with Action/Forms, let's use a Form for the header filter.

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back_to_list')
                ->label('Tampilan Tabel')
                ->icon('heroicon-o-table-cells')
                ->color('gray')
                ->url(JobApplicationResource::getUrl('index')),
            Action::make('select_job_posting')
                ->label('Pilih Lowongan Pekerjaan')
                ->icon('heroicon-o-funnel')
                ->color('gray')
                ->form([
                    Select::make('job_posting_id')
                        ->label('Lowongan')
                        ->options(JobPosting::pluck('title', 'id'))
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

    // Instead of redirecting maybe we can just read from URL query string
    protected function queryString(): array
    {
        return [
            'tableSearch'        => ['as' => 'search', 'except' => ''],
            'activeJobPostingId' => ['as' => 'job_posting_id', 'except' => ''],
        ];
    }

    public function board(Board $board): Board
    {
        return $board
            ->query(
                JobApplication::query()
                    ->when($this->activeJobPostingId, fn (Builder $query) => $query->where('job_posting_id', $this->activeJobPostingId))
            )
            ->recordTitleAttribute('full_name') // Using full_name instead of title
            ->columnIdentifier('current_stage_id')
            ->positionIdentifier('position')
            ->recordActions([
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
            return;
        }

        try {
            $this->baseMoveCard($cardId, $targetColumnId, $afterCardId, $beforeCardId);
        } catch (InvalidArgumentException $exception) {
            Notification::make()
                ->title($exception->getMessage())
                ->danger()
                ->send();

            return;
        }

        $application->refresh();
        $application->histories()->create([
            'from_stage_id' => $fromStageId,
            'to_stage_id'   => $application->current_stage_id,
            'status'        => $application->status,
            'notes'         => __('rekrutmen::filament/resources/job-application.workflow_notes.stage_changed'),
            'performed_by'  => auth()->id(),
        ]);
    }

    protected function getDynamicColumns(): array
    {
        if (! $this->activeJobPostingId) {
            return [
                Column::make('empty')->label('Pilih Lowongan Pekerjaan Dulu'),
            ];
        }

        $jobPosting = $this->resolveActiveJobPosting();

        if (! $jobPosting || ! $jobPosting->rekrutmen_pipeline_id) {
            return [
                Column::make('empty')->label('Belum Ada Pipeline'),
            ];
        }

        $stages = RekrutmenStage::where('rekrutmen_pipeline_id', $jobPosting->rekrutmen_pipeline_id)
            ->orderBy('order_column')
            ->get();

        if ($stages->isEmpty()) {
            return [
                Column::make('empty')->label('Tidak Ada Stage pada Pipeline ini'),
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

    // We can also hook into the onCardDrop or similar if Flowforge fires events,
    // but columnIdentifier updates it automatically!
    // But we need to record history if they dragged it...
    // The instructions say "BISA PAKE relaticle-flowforge" - FlowForge is very advanced and likely handles saving.
    // However, Cesa has a JobApplication history tracking requirement. We can hook into the update event if Flowforge provides one.
}
