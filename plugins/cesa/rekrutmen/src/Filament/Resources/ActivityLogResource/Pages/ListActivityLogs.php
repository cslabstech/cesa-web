<?php

namespace Cesa\Rekrutmen\Filament\Resources\ActivityLogResource\Pages;

use Cesa\Rekrutmen\Enums\ActivityEntryResult;
use Cesa\Rekrutmen\Filament\Resources\ActivityLogResource;
use Cesa\Rekrutmen\Models\JobApplicationHistory;
use Cesa\Rekrutmen\Models\JobPosting;
use Cesa\Rekrutmen\Models\RekrutmenStage;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Livewire\WithPagination;

class ListActivityLogs extends Page
{
    use WithPagination;

    protected static string $resource = ActivityLogResource::class;

    protected string $view = 'rekrutmen::filament.pages.list-activity-logs';

    public ?int $jobPostingId = null;

    public ?int $stageId = null;

    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->icon('heroicon-o-plus-circle')
                ->url(ActivityLogResource::getUrl('create')),
        ];
    }

    public function updating(string $property): void
    {
        if (in_array($property, ['jobPostingId', 'stageId', 'dateFrom', 'dateTo'], true)) {
            $this->resetPage();
        }
    }

    public function resetFilters(): void
    {
        $this->jobPostingId = null;
        $this->stageId = null;
        $this->dateFrom = null;
        $this->dateTo = null;
        $this->resetPage();
    }

    public function deleteActivity(string $activityGroupId): void
    {
        $activity = JobApplicationHistory::query()
            ->where('activity_group_id', $activityGroupId)
            ->firstOrFail();

        Gate::authorize('delete', $activity);

        JobApplicationHistory::query()
            ->where('activity_group_id', $activityGroupId)
            ->delete();

        Notification::make()
            ->title(__('rekrutmen::filament/resources/activity-log.notifications.deleted'))
            ->success()
            ->send();

        $this->resetPage();
    }

    /**
     * @return array<int, string>
     */
    public function getJobPostingOptionsProperty(): array
    {
        return JobPosting::query()
            ->orderBy('title')
            ->pluck('title', 'id')
            ->all();
    }

    /**
     * @return array<int, string>
     */
    public function getStageOptionsProperty(): array
    {
        return RekrutmenStage::query()
            ->orderBy('order_column')
            ->pluck('name', 'id')
            ->all();
    }

    public function getActivitiesProperty(): LengthAwarePaginator
    {
        $activities = $this->filteredActivityEntries()
            ->get()
            ->unique('activity_group_id')
            ->values();

        $perPage = 15;
        $page = $this->getPage();

        return new LengthAwarePaginator(
            $activities->forPage($page, $perPage)->values(),
            $activities->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()],
        );
    }

    /**
     * @return array<string, array{total: int, passed: int, failed: int, pending: int}>
     */
    public function getActivityEntriesProperty(): array
    {
        return $this->filteredActivityEntries()
            ->get()
            ->groupBy('activity_group_id')
            ->map(fn (Collection $entries): array => [
                'total'   => $entries->count(),
                'passed'  => $entries->where('result', ActivityEntryResult::PASSED)->count(),
                'failed'  => $entries->where('result', ActivityEntryResult::FAILED)->count(),
                'pending' => $entries->where('result', ActivityEntryResult::PENDING)->count(),
            ])
            ->all();
    }

    protected function filteredActivityEntries(): Builder
    {
        return JobApplicationHistory::query()
            ->whereNotNull('activity_group_id')
            ->when($this->jobPostingId, function (Builder $query): void {
                $query->whereHas(
                    'jobApplication',
                    fn (Builder $jobApplicationQuery): Builder => $jobApplicationQuery
                        ->where('job_posting_id', $this->jobPostingId)
                );
            })
            ->when($this->stageId, function (Builder $query): void {
                $query->where(function (Builder $query): void {
                    $query->where('from_stage_id', $this->stageId)
                        ->orWhere(function (Builder $query): void {
                            $query->whereNull('from_stage_id')
                                ->where('to_stage_id', $this->stageId);
                        });
                });
            })
            ->when($this->dateFrom, fn (Builder $query, string $dateFrom): Builder => $query->whereDate('activity_date', '>=', $dateFrom))
            ->when($this->dateTo, fn (Builder $query, string $dateTo): Builder => $query->whereDate('activity_date', '<=', $dateTo))
            ->with(['fromStage', 'jobApplication.jobPosting', 'performer'])
            ->orderByDesc('activity_date')
            ->orderByDesc('created_at');
    }
}
