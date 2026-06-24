<?php

namespace Cesa\Rekrutmen\Models;

use Cesa\Rekrutmen\Enums\JobApplicationStatus;
use Cesa\Rekrutmen\Enums\RequestManPowerApprovalStatus;
use Cesa\Rekrutmen\Enums\RequestManPowerFulfillmentStatus;
use Cesa\Rekrutmen\Enums\RequestManPowerStatus;
use Cesa\Rekrutmen\Enums\StatusKebutuhan;
use Cesa\Rekrutmen\Services\MailThrottleService;
use Cesa\Rekrutmen\Services\RequestManPowerApprovalWhatsAppNotifier;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;
use Webkul\Security\Models\User;
use Webkul\Security\Traits\HasNullableCreator;
use Webkul\Support\Models\Company;

class RequestManPower extends Model
{
    use HasFactory, HasNullableCreator, SoftDeletes;

    protected $table = 'rekrutmen_request_man_powers';

    const LEVEL_PEKERJAAN_OPTIONS = ['Staff', 'Leader', 'Coordinator', 'Manager'];

    const STATUS_KEBUTUHAN_OPTIONS = ['New Hiring', 'Replacement'];

    protected $fillable = [
        'company_id',
        'division_id',
        'email_address',
        'nama_pengaju',
        'posisi_pengaju',
        'tanggal_pengajuan',
        'posisi_dibutuhkan',
        'lokasi_penempatan',
        'status_kebutuhan',
        'divisi',
        'level_pekerjaan',
        'nama_karyawan_replacement',
        'jumlah_karyawan_dibutuhkan',
        'estimasi_tanggal_join',
        'requirements_kualifikasi',
        'job_description',
        'status_response_id',
        'keterangan',
        'status',
        'approved_by',
        'job_posting_id',
        'hold_reason',
        'held_at',
        'held_by',
        'resumed_at',
        'resumed_by',
        'hold_job_posting_was_published',
    ];

    protected function casts(): array
    {
        return [
            'status_kebutuhan'               => StatusKebutuhan::class,
            'status'                         => RequestManPowerStatus::class,
            'company_id'                     => 'integer',
            'division_id'                    => 'integer',
            'tanggal_pengajuan'              => 'date',
            'estimasi_tanggal_join'          => 'date',
            'jumlah_karyawan_dibutuhkan'     => 'integer',
            'status_response_id'             => 'string',
            'job_posting_id'                 => 'integer',
            'held_at'                        => 'datetime',
            'resumed_at'                     => 'datetime',
            'hold_job_posting_was_published' => 'boolean',
            'created_at'                     => 'datetime',
            'updated_at'                     => 'datetime',
            'deleted_at'                     => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $request): void {
            if (blank($request->status_response_id)) {
                $request->status_response_id = (string) Str::uuid();
            }
        });

        static::saving(function (self $request): void {
            if (is_string($request->email_address)) {
                $request->email_address = trim($request->email_address);
            }

            if (blank($request->email_address)) {
                throw ValidationException::withMessages([
                    'email_address' => __('validation.required', [
                        'attribute' => Str::lower(__('rekrutmen::livewire/public-request-man-power-form.fields.email_address')),
                    ]),
                ]);
            }

            $request->syncBusinessEntitySnapshot();
            $request->syncDivisionSnapshot();
        });
    }

    protected function namaKaryawanReplacement(): Attribute
    {
        return Attribute::make(
            set: function (?string $value, array $attributes): ?string {
                $statusKebutuhan = $attributes['status_kebutuhan'] ?? $this->status_kebutuhan;
                $statusKebutuhanValue = $statusKebutuhan instanceof StatusKebutuhan
                    ? $statusKebutuhan->value
                    : $statusKebutuhan;

                if ($statusKebutuhanValue !== StatusKebutuhan::REPLACEMENT->value) {
                    return null;
                }

                if (blank($value)) {
                    return null;
                }

                return trim($value);
            },
        );
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by')->withTrashed();
    }

    public function heldBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'held_by')->withTrashed();
    }

    public function resumedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resumed_by')->withTrashed();
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id')->withTrashed();
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class, 'division_id')->withTrashed();
    }

    public function jobPosting(): BelongsTo
    {
        return $this->belongsTo(JobPosting::class, 'job_posting_id')->withTrashed();
    }

    public function sourceJobPosting(): HasOne
    {
        return $this->hasOne(JobPosting::class, 'request_man_power_id')->withTrashed();
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(RequestManPowerApproval::class, 'request_man_power_id')
            ->orderBy('step_order');
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(RequestManPowerStatusHistory::class, 'request_man_power_id')
            ->with('actor')
            ->orderByDesc('created_at')
            ->orderByDesc('id');
    }

    public function currentPendingApproval(): HasOne
    {
        return $this->hasOne(RequestManPowerApproval::class, 'request_man_power_id')
            ->where('status', RequestManPowerApprovalStatus::PENDING->value)
            ->orderBy('step_order');
    }

    public function getBusinessEntityNameAttribute(): ?string
    {
        return $this->company?->name;
    }

    public function getDivisionNameAttribute(): ?string
    {
        if ($this->division?->name) {
            return $this->division->name;
        }

        if (! is_string($this->divisi)) {
            return null;
        }

        $divisionName = trim($this->divisi);

        return $divisionName !== '' ? $divisionName : null;
    }

    public function isReplacement(): bool
    {
        return $this->status_kebutuhan === StatusKebutuhan::REPLACEMENT;
    }

    public function getTanggalPengajuanFormattedAttribute(): string
    {
        return $this->tanggal_pengajuan?->translatedFormat('d F Y') ?? '-';
    }

    public function getEstimasiTanggalJoinFormattedAttribute(): string
    {
        return $this->estimasi_tanggal_join?->translatedFormat('d F Y') ?? '-';
    }

    public function getPublicProgressUrl(): string
    {
        if (blank($this->status_response_id)) {
            return url('man-power');
        }

        return url('man-power/progress/'.$this->status_response_id);
    }

    /**
     * @return array<string, string>
     */
    public static function getTranslatedLevelPekerjaanOptions(): array
    {
        return collect(self::LEVEL_PEKERJAAN_OPTIONS)
            ->mapWithKeys(fn (string $option) => [
                $option => __('rekrutmen::enums/level-pekerjaan.'.Str::snake($option)),
            ])
            ->all();
    }

    public function scopeByDivisi(Builder $query, string $divisi): Builder
    {
        return $query->where('divisi', $divisi);
    }

    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    public function scopeWhereFulfillmentStatus(Builder $query, RequestManPowerFulfillmentStatus|string|null $status): Builder
    {
        if (is_string($status)) {
            $status = RequestManPowerFulfillmentStatus::tryFrom($status);
        }

        if (! $status instanceof RequestManPowerFulfillmentStatus) {
            return $query;
        }

        return match ($status) {
            RequestManPowerFulfillmentStatus::FULFILLED        => $this->applyFulfilledScope($query),
            RequestManPowerFulfillmentStatus::CLOSED           => $this->applyClosedFulfillmentScope($query),
            RequestManPowerFulfillmentStatus::PENDING_APPROVAL => $query
                ->where($this->qualifyColumn('status'), RequestManPowerStatus::PENDING->value),
            RequestManPowerFulfillmentStatus::ON_HOLD => $query
                ->where($this->qualifyColumn('status'), RequestManPowerStatus::HOLD->value),
            RequestManPowerFulfillmentStatus::IN_PROCESS => $this->applyNotFulfilledScope($query)
                ->where(fn (Builder $query): Builder => $this->applyOpenScope($query))
                ->whereRaw($this->applicationCountSql(JobApplicationStatus::IN_PROGRESS).' > 0', [
                    JobApplicationStatus::IN_PROGRESS->value,
                ]),
            RequestManPowerFulfillmentStatus::NO_CANDIDATE => $this->applyNotFulfilledScope($query)
                ->where(fn (Builder $query): Builder => $this->applyOpenScope($query))
                ->whereRaw($this->applicationCountSql().' = 0'),
            RequestManPowerFulfillmentStatus::UNFULFILLED => $this->applyNotFulfilledScope($query)
                ->where(fn (Builder $query): Builder => $this->applyOpenScope($query))
                ->whereRaw($this->applicationCountSql(JobApplicationStatus::IN_PROGRESS).' = 0', [
                    JobApplicationStatus::IN_PROGRESS->value,
                ])
                ->whereRaw($this->applicationCountSql().' > 0'),
        };
    }

    public function scopeByTanggal(Builder $query, string $from, string $to): Builder
    {
        return $query->whereBetween('tanggal_pengajuan', [$from, $to]);
    }

    public function neededHeadcount(): int
    {
        $jobPosting = $this->jobPosting;

        if ($jobPosting) {
            return max(1, $jobPosting->totalNeeded());
        }

        return max(1, (int) ($this->jumlah_karyawan_dibutuhkan ?? 1));
    }

    public function totalCandidatesCount(): int
    {
        return $this->applicationStatusCount();
    }

    public function hiredCandidatesCount(): int
    {
        return $this->applicationStatusCount(JobApplicationStatus::HIRED);
    }

    public function inProcessCandidatesCount(): int
    {
        return $this->applicationStatusCount(JobApplicationStatus::IN_PROGRESS);
    }

    public function fulfillmentStatus(): RequestManPowerFulfillmentStatus
    {
        if ($this->status === RequestManPowerStatus::PENDING) {
            return RequestManPowerFulfillmentStatus::PENDING_APPROVAL;
        }

        if ($this->status === RequestManPowerStatus::HOLD) {
            return RequestManPowerFulfillmentStatus::ON_HOLD;
        }

        if ($this->status === RequestManPowerStatus::REJECTED || $this->jobPosting?->trashed()) {
            return RequestManPowerFulfillmentStatus::CLOSED;
        }

        if ($this->hiredCandidatesCount() >= $this->neededHeadcount()) {
            return RequestManPowerFulfillmentStatus::FULFILLED;
        }

        if ($this->isFulfillmentClosed()) {
            return RequestManPowerFulfillmentStatus::CLOSED;
        }

        if ($this->inProcessCandidatesCount() > 0) {
            return RequestManPowerFulfillmentStatus::IN_PROCESS;
        }

        if ($this->totalCandidatesCount() === 0) {
            return RequestManPowerFulfillmentStatus::NO_CANDIDATE;
        }

        return RequestManPowerFulfillmentStatus::UNFULFILLED;
    }

    public function fulfillmentSummary(): string
    {
        return __('rekrutmen::filament/resources/request-man-power.table.fulfillment_summary', [
            'hired'      => $this->hiredCandidatesCount(),
            'needed'     => $this->neededHeadcount(),
            'in_process' => $this->inProcessCandidatesCount(),
            'total'      => $this->totalCandidatesCount(),
        ]);
    }

    public function isFulfillmentClosed(): bool
    {
        if ($this->status === RequestManPowerStatus::REJECTED) {
            return true;
        }

        $jobPosting = $this->jobPosting;

        if (! $jobPosting) {
            return false;
        }

        if ($jobPosting->trashed()) {
            return true;
        }

        return (bool) $jobPosting->closing_date?->lt(today());
    }

    public function normalizedDivision(): ?string
    {
        $divisionName = $this->division_name;

        if (! is_string($divisionName)) {
            return null;
        }

        $normalizedDivision = mb_strtolower(trim($divisionName));

        return $normalizedDivision !== '' ? $normalizedDivision : null;
    }

    private function applicationStatusCount(?JobApplicationStatus $status = null): int
    {
        $jobPosting = $this->jobPosting;

        if (! $jobPosting) {
            return 0;
        }

        if ($jobPosting->relationLoaded('applications')) {
            $applications = $jobPosting->applications;

            if (! $status) {
                return $applications->count();
            }

            return $applications
                ->filter(function (JobApplication $application) use ($status): bool {
                    if ($application->status instanceof JobApplicationStatus) {
                        return $application->status === $status;
                    }

                    return $application->status === $status->value;
                })
                ->count();
        }

        $query = $jobPosting->applications();

        if ($status) {
            $query->where('status', $status->value);
        }

        return $query->count();
    }

    private function applyFulfilledScope(Builder $query): Builder
    {
        return $query
            ->where($this->qualifyColumn('status'), RequestManPowerStatus::APPROVED->value)
            ->whereDoesntHave('jobPosting', fn (Builder $query): Builder => $query
                ->whereNotNull((new JobPosting)->qualifyColumn('deleted_at')))
            ->whereRaw($this->applicationCountSql(JobApplicationStatus::HIRED).' >= '.$this->neededHeadcountSql(), [
                JobApplicationStatus::HIRED->value,
                ...$this->neededHeadcountSqlBindings(),
            ]);
    }

    private function applyNotFulfilledScope(Builder $query): Builder
    {
        return $query->whereRaw($this->applicationCountSql(JobApplicationStatus::HIRED).' < '.$this->neededHeadcountSql(), [
            JobApplicationStatus::HIRED->value,
            ...$this->neededHeadcountSqlBindings(),
        ]);
    }

    private function applyClosedFulfillmentScope(Builder $query): Builder
    {
        $jobPosting = new JobPosting;

        return $query->where(function (Builder $query) use ($jobPosting): void {
            $query
                ->where($this->qualifyColumn('status'), RequestManPowerStatus::REJECTED->value)
                ->orWhereHas('jobPosting', fn (Builder $query): Builder => $query
                    ->whereNotNull($jobPosting->qualifyColumn('deleted_at')))
                ->orWhere(function (Builder $query) use ($jobPosting): void {
                    $this->applyNotFulfilledScope($query)
                        ->whereHas('jobPosting', fn (Builder $query): Builder => $query
                            ->whereDate($jobPosting->qualifyColumn('closing_date'), '<', today()));
                });
        });
    }

    private function applyOpenScope(Builder $query): Builder
    {
        $jobPosting = new JobPosting;

        return $query
            ->where($this->qualifyColumn('status'), RequestManPowerStatus::APPROVED->value)
            ->where(function (Builder $query) use ($jobPosting): void {
                $query
                    ->whereDoesntHave('jobPosting')
                    ->orWhereHas('jobPosting', function (Builder $query) use ($jobPosting): void {
                        $query
                            ->whereNull($jobPosting->qualifyColumn('deleted_at'))
                            ->where(function (Builder $query) use ($jobPosting): void {
                                $query
                                    ->whereNull($jobPosting->qualifyColumn('closing_date'))
                                    ->orWhereDate($jobPosting->qualifyColumn('closing_date'), '>=', today());
                            });
                    });
            });
    }

    private function applicationCountSql(?JobApplicationStatus $status = null): string
    {
        $application = new JobApplication;
        $applicationTable = $application->getTable();

        $conditions = [
            "{$applicationTable}.job_posting_id = {$this->qualifyColumn('job_posting_id')}",
            "{$applicationTable}.deleted_at is null",
        ];

        if ($status) {
            $conditions[] = "{$applicationTable}.status = ?";
        }

        return "(select count(*) from {$applicationTable} where ".implode(' and ', $conditions).')';
    }

    private function neededHeadcountSql(): string
    {
        $linkedRequestManPowers = 'linked_request_man_powers';
        $linkedHeadcountSql = implode(' ', [
            '(select sum('.$linkedRequestManPowers.'.jumlah_karyawan_dibutuhkan)',
            'from '.$this->getTable().' as '.$linkedRequestManPowers,
            'where '.$linkedRequestManPowers.'.job_posting_id = '.$this->qualifyColumn('job_posting_id'),
            'and '.$linkedRequestManPowers.'.deleted_at is null',
            'and '.$linkedRequestManPowers.'.status in (?, ?))',
        ]);
        $fallbackHeadcountSql = 'COALESCE(NULLIF('.$this->qualifyColumn('jumlah_karyawan_dibutuhkan').', 0), 1)';

        return 'COALESCE(NULLIF('.$linkedHeadcountSql.', 0), '.$fallbackHeadcountSql.')';
    }

    /**
     * @return array<int, string>
     */
    private function neededHeadcountSqlBindings(): array
    {
        return [
            RequestManPowerStatus::APPROVED->value,
            RequestManPowerStatus::HOLD->value,
        ];
    }

    /**
     * @return Collection<int, Approver>
     */
    public function approvalApprovers(): Collection
    {
        return Approver::query()
            ->matchingRequest($this)
            ->get()
            ->unique(fn (Approver $approver): string => Str::lower(trim($approver->email)))
            ->values();
    }

    /**
     * @return Collection<int, RequestManPowerApproval>
     */
    public function initializeApprovalWorkflow(bool $replaceExisting = false): Collection
    {
        return DB::transaction(function () use ($replaceExisting): Collection {
            if (! $replaceExisting && $this->approvals()->exists()) {
                return $this->approvals()->get();
            }

            if ($replaceExisting) {
                $this->approvals()->delete();
            }

            $approvers = $this->approvalApprovers()
                ->filter(fn (Approver $approver): bool => filled($approver->email))
                ->values();

            if ($approvers->isEmpty()) {
                return collect();
            }

            $timestamp = now();

            $payload = $approvers
                ->map(function (Approver $approver, int $index) use ($timestamp): array {
                    $isFirstStep = $index === 0;

                    return [
                        'request_man_power_id' => $this->getKey(),
                        'approver_id'          => $approver->getKey(),
                        'approver_name'        => $approver->name,
                        'approver_email'       => $approver->email,
                        'approver_title'       => $approver->title,
                        'step_order'           => $index + 1,
                        'status'               => $isFirstStep
                            ? RequestManPowerApprovalStatus::PENDING->value
                            : RequestManPowerApprovalStatus::WAITING->value,
                        'action_token'      => $isFirstStep ? (string) Str::uuid() : null,
                        'action_expires_at' => null,
                        'notified_at'       => null,
                        'acted_at'          => null,
                        'notes'             => null,
                        'acted_by_user_id'  => null,
                        'created_at'        => $timestamp,
                        'updated_at'        => $timestamp,
                    ];
                })
                ->all();

            RequestManPowerApproval::query()->insert($payload);

            return $this->approvals()->get();
        });
    }

    public function sendSubmittedNotification(): void
    {
        if (blank($this->email_address)) {
            return;
        }

        try {
            $notification = new RequestManPowerSubmittedNotification($this);
            $delaySeconds = app(MailThrottleService::class)->getDispatchDelaySeconds();

            if ($delaySeconds > 0) {
                $notification->delay(now()->addSeconds($delaySeconds));
            }

            NotificationFacade::route('mail', $this->email_address)
                ->notify($notification);
        } catch (Throwable $e) {
            Log::error('Failed to send request man power submitted notification.', [
                'request_man_power_id' => $this->getKey(),
                'email'                => $this->email_address,
                'exception'            => $e,
            ]);
        }
    }

    public function sendStatusChangedNotification(mixed $fromStatus, mixed $toStatus): void
    {
        if (blank($this->email_address)) {
            return;
        }

        $from = $this->normalizeStatus($fromStatus);
        $to = $this->normalizeStatus($toStatus);

        if (! $to) {
            return;
        }

        if ($from?->value === $to->value) {
            return;
        }

        try {
            $notification = new RequestManPowerStatusChangedNotification($this, $from, $to);
            $delaySeconds = app(MailThrottleService::class)->getDispatchDelaySeconds();

            if ($delaySeconds > 0) {
                $notification->delay(now()->addSeconds($delaySeconds));
            }

            NotificationFacade::route('mail', $this->email_address)
                ->notify($notification);
        } catch (Throwable $e) {
            Log::error('Failed to send request man power status changed notification.', [
                'request_man_power_id' => $this->getKey(),
                'email'                => $this->email_address,
                'from_status'          => $from?->value,
                'to_status'            => $to->value,
                'exception'            => $e,
            ]);
        }
    }

    public function sendApprovalRequestNotifications(): ?RequestManPowerApproval
    {
        return $this->initializeAndNotifyApprovalWorkflow();
    }

    public function initializeAndNotifyApprovalWorkflow(bool $replaceExisting = false, bool $rotateToken = false): ?RequestManPowerApproval
    {
        $approvals = $this->initializeApprovalWorkflow($replaceExisting);

        if ($approvals->isEmpty()) {
            $this->logMissingApprovalWorkflow();

            return null;
        }

        return $this->notifyCurrentPendingApproval($rotateToken);
    }

    public function hasMissingApprovalWorkflow(): bool
    {
        if ($this->normalizeStatus($this->status) !== RequestManPowerStatus::PENDING) {
            return false;
        }

        $approvalsCount = $this->getAttribute('approvals_count');

        if (is_numeric($approvalsCount)) {
            return (int) $approvalsCount === 0;
        }

        if ($this->relationLoaded('approvals')) {
            return $this->approvals->isEmpty();
        }

        return ! $this->approvals()->exists();
    }

    public function notifyCurrentPendingApproval(bool $rotateToken = false): ?RequestManPowerApproval
    {
        $approval = $this->currentPendingApproval()->first();

        if (! $approval) {
            return null;
        }

        if (blank($approval->approver_email)) {
            return $approval;
        }

        $approval->forceFill([
            'action_token' => $rotateToken || blank($approval->action_token)
                ? (string) Str::uuid()
            : $approval->action_token,
            'action_expires_at' => now()->addMinutes(
                (int) config('rekrutmen.security.approval_link_expiration_minutes', 10080)
            ),
            'notified_at' => now(),
        ])->save();

        try {
            $notification = new RequestManPowerApprovalRequestedNotification($this, $approval);
            $delaySeconds = app(MailThrottleService::class)->getDispatchDelaySeconds();

            if ($delaySeconds > 0) {
                $notification->delay(now()->addSeconds($delaySeconds));
            }

            NotificationFacade::route('mail', $approval->approver_email)
                ->notify($notification);
        } catch (Throwable $exception) {
            Log::error('Failed to send request man power approval request notification.', [
                'request_man_power_id' => $this->getKey(),
                'approval_id'          => $approval->getKey(),
                'approver_id'          => $approval->approver_id,
                'email'                => $approval->approver_email,
                'exception'            => $exception,
            ]);
        }

        try {
            app(RequestManPowerApprovalWhatsAppNotifier::class)->send($this, $approval);
        } catch (Throwable $exception) {
            Log::error('Failed to queue request man power approval WhatsApp notification.', [
                'request_man_power_id' => $this->getKey(),
                'approval_id'          => $approval->getKey(),
                'approver_id'          => $approval->approver_id,
                'exception'            => $exception,
            ]);
        }

        return $approval->fresh();
    }

    public function approveBy(?int $approverId = null): void
    {
        $previousStatus = $this->status;

        DB::transaction(function () use ($approverId, $previousStatus): void {
            $this->createJobPostingIfMissing();

            $this->update([
                'status'      => RequestManPowerStatus::APPROVED,
                'approved_by' => $approverId,
            ]);

            $this->recordStatusHistory($previousStatus, RequestManPowerStatus::APPROVED, $approverId);
        });

        $this->sendStatusChangedNotification($previousStatus, RequestManPowerStatus::APPROVED);
    }

    public function rejectBy(?int $approverId = null): void
    {
        $previousStatus = $this->status;

        DB::transaction(function () use ($approverId, $previousStatus): void {
            $this->update([
                'status'      => RequestManPowerStatus::REJECTED,
                'approved_by' => $approverId,
            ]);

            $this->unpublishLinkedJobPosting();

            $this->recordStatusHistory($previousStatus, RequestManPowerStatus::REJECTED, $approverId);
        });

        $this->sendStatusChangedNotification($previousStatus, RequestManPowerStatus::REJECTED);
    }

    public function markPending(?int $actorId = null): void
    {
        $previousStatus = $this->status;

        DB::transaction(function () use ($actorId, $previousStatus): void {
            $this->update([
                'status'      => RequestManPowerStatus::PENDING,
                'approved_by' => null,
            ]);

            $this->initializeApprovalWorkflow(replaceExisting: true);
            $this->unpublishLinkedJobPosting();
            $this->recordStatusHistory($previousStatus, RequestManPowerStatus::PENDING, $actorId ?? Auth::id());
        });

        $this->sendStatusChangedNotification($previousStatus, RequestManPowerStatus::PENDING);
        $this->initializeAndNotifyApprovalWorkflow(replaceExisting: false, rotateToken: true);
    }

    public function markOnHold(?int $actorId = null, string $holdReason = ''): void
    {
        $reason = trim($holdReason);

        if ($reason === '') {
            throw ValidationException::withMessages([
                'hold_reason' => __('validation.required', [
                    'attribute' => Str::lower(__('rekrutmen::filament/resources/request-man-power.form.fields.hold_reason')),
                ]),
            ]);
        }

        $previousStatus = $this->status;
        $resolvedActorId = $actorId ?? Auth::id();
        $timestamp = now();

        DB::transaction(function () use ($previousStatus, $reason, $resolvedActorId, $timestamp): void {
            $jobPosting = $this->jobPosting()->first();
            $wasPublished = (bool) ($jobPosting?->is_published ?? false);

            $this->update([
                'status'                         => RequestManPowerStatus::HOLD,
                'approved_by'                    => $this->approved_by ?? $resolvedActorId,
                'hold_reason'                    => $reason,
                'held_at'                        => $timestamp,
                'held_by'                        => $resolvedActorId,
                'resumed_at'                     => null,
                'resumed_by'                     => null,
                'hold_job_posting_was_published' => $wasPublished,
            ]);

            if ($jobPosting && ! $this->jobPostingHasOtherApprovedRequests($jobPosting)) {
                $jobPosting->update([
                    'is_published' => false,
                ]);
            }

            $this->recordStatusHistory($previousStatus, RequestManPowerStatus::HOLD, $resolvedActorId, $reason);
        });

        $this->sendStatusChangedNotification($previousStatus, RequestManPowerStatus::HOLD);
    }

    public function resumeFromHold(?int $actorId = null): void
    {
        $previousStatus = $this->status;
        $resolvedActorId = $actorId ?? Auth::id();
        $timestamp = now();

        DB::transaction(function () use ($previousStatus, $resolvedActorId, $timestamp): void {
            $jobPosting = $this->createJobPostingIfMissing();

            $this->update([
                'status'      => RequestManPowerStatus::APPROVED,
                'approved_by' => $this->approved_by ?? $resolvedActorId,
                'resumed_at'  => $timestamp,
                'resumed_by'  => $resolvedActorId,
            ]);

            if ($this->hold_job_posting_was_published) {
                $jobPosting->update([
                    'is_published' => true,
                ]);
            }

            $this->recordStatusHistory($previousStatus, RequestManPowerStatus::APPROVED, $resolvedActorId);
        });

        $this->sendStatusChangedNotification($previousStatus, RequestManPowerStatus::APPROVED);
    }

    public function isCurrentPendingApproval(RequestManPowerApproval $approval): bool
    {
        if ($approval->request_man_power_id !== $this->getKey()) {
            return false;
        }

        if ($this->status !== RequestManPowerStatus::PENDING) {
            return false;
        }

        $currentPendingApproval = $this->currentPendingApproval()->first();

        return $currentPendingApproval?->is($approval) && $approval->isPending();
    }

    public function approveApprovalStep(RequestManPowerApproval $approval, ?string $notes = null): void
    {
        if (! $this->isCurrentPendingApproval($approval)) {
            throw new RuntimeException(
                __('rekrutmen::livewire/public-request-man-power-approval-page.notifications.already_processed')
            );
        }

        $previousStatus = null;
        $actedByUserId = null;
        $nextApprovalId = null;

        DB::transaction(function () use ($approval, $notes, &$actedByUserId, &$previousStatus, &$nextApprovalId): void {
            [$lockedRequest, $lockedApproval] = $this->lockProcessableApprovalStep($approval);

            $previousStatus = $lockedRequest->status;
            $actedByUserId = $lockedRequest->resolveMatchedUserIdByEmail($lockedApproval->approver_email);

            $lockedApproval->forceFill([
                'status'            => RequestManPowerApprovalStatus::APPROVED,
                'notes'             => $notes,
                'acted_at'          => now(),
                'acted_by_user_id'  => $actedByUserId,
                'action_expires_at' => now(),
            ])->save();

            $nextApproval = $lockedRequest->approvals()
                ->where('step_order', '>', $lockedApproval->step_order)
                ->orderBy('step_order')
                ->lockForUpdate()
                ->first();

            if ($nextApproval) {
                $nextApproval->forceFill([
                    'status'       => RequestManPowerApprovalStatus::PENDING,
                    'action_token' => (string) Str::uuid(),
                    'notified_at'  => null,
                ])->save();

                $nextApprovalId = $nextApproval->getKey();

                return;
            }

            $lockedRequest->createJobPostingIfMissing();

            $lockedRequest->update([
                'status'      => RequestManPowerStatus::APPROVED,
                'approved_by' => $actedByUserId,
            ]);

            $lockedRequest->recordStatusHistory($previousStatus, RequestManPowerStatus::APPROVED, $actedByUserId, $notes);
        });

        $this->refresh();

        if ($nextApprovalId) {
            $this->notifyCurrentPendingApproval();

            return;
        }

        $this->sendStatusChangedNotification($previousStatus, RequestManPowerStatus::APPROVED);
    }

    public function rejectApprovalStep(RequestManPowerApproval $approval, ?string $notes = null): void
    {
        if (! $this->isCurrentPendingApproval($approval)) {
            throw new RuntimeException(
                __('rekrutmen::livewire/public-request-man-power-approval-page.notifications.already_processed')
            );
        }

        $previousStatus = null;
        $actedByUserId = null;

        DB::transaction(function () use ($approval, $notes, &$actedByUserId, &$previousStatus): void {
            [$lockedRequest, $lockedApproval] = $this->lockProcessableApprovalStep($approval);

            $previousStatus = $lockedRequest->status;
            $actedByUserId = $lockedRequest->resolveMatchedUserIdByEmail($lockedApproval->approver_email);

            $lockedApproval->forceFill([
                'status'            => RequestManPowerApprovalStatus::REJECTED,
                'notes'             => $notes,
                'acted_at'          => now(),
                'acted_by_user_id'  => $actedByUserId,
                'action_expires_at' => now(),
            ])->save();

            $lockedRequest->approvals()
                ->where('step_order', '>', $lockedApproval->step_order)
                ->update([
                    'status'            => RequestManPowerApprovalStatus::WAITING->value,
                    'action_token'      => null,
                    'action_expires_at' => null,
                    'notified_at'       => null,
                    'acted_at'          => null,
                    'notes'             => null,
                    'acted_by_user_id'  => null,
                    'updated_at'        => now(),
                ]);

            $lockedRequest->update([
                'status'      => RequestManPowerStatus::REJECTED,
                'approved_by' => $actedByUserId,
            ]);

            $lockedRequest->unpublishLinkedJobPosting();
            $lockedRequest->recordStatusHistory($previousStatus, RequestManPowerStatus::REJECTED, $actedByUserId, $notes);
        });

        $this->refresh();
        $this->sendStatusChangedNotification($previousStatus, RequestManPowerStatus::REJECTED);
    }

    /**
     * @return array{0: self, 1: RequestManPowerApproval}
     */
    private function lockProcessableApprovalStep(RequestManPowerApproval $approval): array
    {
        $requestManPower = self::query()
            ->whereKey($this->getKey())
            ->lockForUpdate()
            ->firstOrFail();

        $lockedApproval = RequestManPowerApproval::query()
            ->whereKey($approval->getKey())
            ->lockForUpdate()
            ->firstOrFail();

        if (! $requestManPower->isCurrentPendingApproval($lockedApproval)) {
            throw new RuntimeException(
                __('rekrutmen::livewire/public-request-man-power-approval-page.notifications.already_processed')
            );
        }

        if ($lockedApproval->hasExpiredActionLink()) {
            throw new RuntimeException(
                __('rekrutmen::livewire/public-request-man-power-approval-page.notifications.link_expired')
            );
        }

        return [$requestManPower, $lockedApproval];
    }

    public function createJobPostingIfMissing(): JobPosting
    {
        $existingPosting = $this->resolveLinkedJobPosting();
        $title = $this->buildJobPostingTitle();

        if ($title === '') {
            $title = __('rekrutmen::filament/resources/job-posting.generated.title', ['id' => $this->getKey()]);
        }

        $baseSlug = Str::slug($title);
        $baseSlug = $baseSlug !== '' ? $baseSlug : 'job-posting-'.$this->getKey();

        if ($existingPosting) {
            if ($existingPosting->trashed()) {
                $existingPosting->restore();
            }

            if ((int) $existingPosting->request_man_power_id === (int) $this->getKey()) {
                $pipelineId = $existingPosting->rekrutmen_pipeline_id ?? $this->resolveDefaultPipelineId();
                $slug = $this->resolveAvailableJobPostingSlug($baseSlug, $existingPosting->getKey());

                $existingPosting->update([
                    'rekrutmen_pipeline_id' => $pipelineId,
                    'title'                 => $title,
                    'slug'                  => $slug,
                    'description'           => $this->job_description,
                    'requirements'          => $this->requirements_kualifikasi,
                    'location'              => $this->lokasi_penempatan,
                    'closing_date'          => $this->resolveJobPostingClosingDate($existingPosting),
                ]);
            } else {
                $this->extendJobPostingClosingDate($existingPosting);
            }

            $this->associateWithJobPosting($existingPosting);

            return $existingPosting->fresh();
        }

        $compatiblePosting = $this->findCompatibleJobPosting();

        if ($compatiblePosting) {
            $this->associateWithJobPosting($compatiblePosting);
            $this->extendJobPostingClosingDate($compatiblePosting);

            return $compatiblePosting->fresh();
        }

        $jobPosting = JobPosting::query()->create([
            'request_man_power_id'  => $this->getKey(),
            'rekrutmen_pipeline_id' => $this->resolveDefaultPipelineId(),
            'title'                 => $title,
            'slug'                  => $this->resolveAvailableJobPostingSlug($baseSlug),
            'description'           => $this->job_description,
            'requirements'          => $this->requirements_kualifikasi,
            'location'              => $this->lokasi_penempatan,
            'is_published'          => false,
            'closing_date'          => $this->estimasi_tanggal_join,
        ]);

        $this->associateWithJobPosting($jobPosting);

        return $jobPosting->fresh();
    }

    private function resolveLinkedJobPosting(): ?JobPosting
    {
        if (is_numeric($this->job_posting_id)) {
            $jobPosting = JobPosting::query()
                ->withTrashed()
                ->whereKey((int) $this->job_posting_id)
                ->first();

            if ($jobPosting) {
                return $jobPosting;
            }
        }

        return JobPosting::query()
            ->withTrashed()
            ->where('request_man_power_id', $this->getKey())
            ->first();
    }

    private function findCompatibleJobPosting(): ?JobPosting
    {
        $position = $this->normalizedJobPostingMatchValue($this->posisi_dibutuhkan);
        $location = $this->normalizedJobPostingMatchValue($this->lokasi_penempatan);

        if ($position === null || $location === null) {
            return null;
        }

        return JobPosting::query()
            ->where(function (Builder $query): void {
                $query->whereNull('closing_date')
                    ->orWhereDate('closing_date', '>=', today());
            })
            ->where(function (Builder $query) use ($position, $location): void {
                $query->whereHas(
                    'requestManPowers',
                    fn (Builder $requestQuery): Builder => $this->applyCompatibleRequestScope(
                        $requestQuery,
                        $position,
                        $location,
                    )
                )->orWhereHas(
                    'requestManPower',
                    fn (Builder $requestQuery): Builder => $this
                        ->applyCompatibleRequestScope($requestQuery, $position, $location)
                        ->where(function (Builder $query): void {
                            $query->whereNull($this->qualifyColumn('job_posting_id'))
                                ->orWhereColumn(
                                    $this->qualifyColumn('job_posting_id'),
                                    (new JobPosting)->qualifyColumn('id'),
                                );
                        })
                );
            })
            ->orderByDesc('is_published')
            ->orderByDesc('id')
            ->first();
    }

    private function applyCompatibleRequestScope(Builder $query, string $position, string $location): Builder
    {
        $query
            ->whereRaw('LOWER(TRIM(posisi_dibutuhkan)) = ?', [$position])
            ->whereRaw('LOWER(TRIM(lokasi_penempatan)) = ?', [$location])
            ->whereNull($this->qualifyColumn('deleted_at'))
            ->where('status', RequestManPowerStatus::APPROVED->value);

        if (is_numeric($this->company_id)) {
            $query->where('company_id', (int) $this->company_id);
        }

        if (is_numeric($this->division_id)) {
            return $query->where('division_id', (int) $this->division_id);
        }

        $division = $this->normalizedDivision();

        if ($division !== null) {
            $query->whereRaw('LOWER(TRIM(divisi)) = ?', [$division]);
        }

        return $query;
    }

    private function associateWithJobPosting(JobPosting $jobPosting): void
    {
        if ((int) $this->job_posting_id === (int) $jobPosting->getKey()) {
            $this->setRelation('jobPosting', $jobPosting);

            return;
        }

        $this->forceFill([
            'job_posting_id' => $jobPosting->getKey(),
        ])->save();

        $this->setRelation('jobPosting', $jobPosting);
    }

    private function extendJobPostingClosingDate(JobPosting $jobPosting): void
    {
        if (! $this->estimasi_tanggal_join) {
            return;
        }

        if ($jobPosting->closing_date && $jobPosting->closing_date->greaterThanOrEqualTo($this->estimasi_tanggal_join)) {
            return;
        }

        $jobPosting->update([
            'closing_date' => $this->estimasi_tanggal_join,
        ]);
    }

    private function resolveJobPostingClosingDate(JobPosting $jobPosting): ?string
    {
        $linkedClosingDate = $jobPosting->requestManPowers()
            ->whereKeyNot($this->getKey())
            ->whereNull($this->qualifyColumn('deleted_at'))
            ->whereIn('status', [
                RequestManPowerStatus::APPROVED->value,
                RequestManPowerStatus::HOLD->value,
            ])
            ->max('estimasi_tanggal_join');

        return collect([
            $this->estimasi_tanggal_join?->toDateString(),
            is_string($linkedClosingDate) ? $linkedClosingDate : null,
        ])
            ->filter()
            ->max();
    }

    private function buildJobPostingTitle(): string
    {
        return trim(implode(' ', array_filter([
            is_string($this->posisi_dibutuhkan) ? trim($this->posisi_dibutuhkan) : $this->posisi_dibutuhkan,
            is_string($this->lokasi_penempatan) ? trim($this->lokasi_penempatan) : $this->lokasi_penempatan,
        ])));
    }

    private function logMissingApprovalWorkflow(): void
    {
        Log::info('Request man power approval workflow skipped because no active approver matched the request scope; request remains pending for manual handling.', [
            'request_man_power_id' => $this->getKey(),
            'company_id'           => $this->company_id,
            'division_id'          => $this->division_id,
            'division_snapshot'    => $this->divisi,
            'status'               => $this->normalizeStatus($this->status)?->value,
        ]);
    }

    private function normalizedJobPostingMatchValue(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $normalizedValue = mb_strtolower(trim($value));

        return $normalizedValue !== '' ? $normalizedValue : null;
    }

    private function normalizeStatus(mixed $status): ?RequestManPowerStatus
    {
        if ($status instanceof RequestManPowerStatus) {
            return $status;
        }

        if (! is_string($status) || blank($status)) {
            return null;
        }

        return RequestManPowerStatus::tryFrom($status);
    }

    private function recordStatusHistory(
        mixed $fromStatus,
        RequestManPowerStatus $toStatus,
        ?int $actorId = null,
        ?string $reason = null,
    ): void {
        $from = $this->normalizeStatus($fromStatus);

        if ($from?->value === $toStatus->value) {
            return;
        }

        $this->statusHistories()->create([
            'from_status'      => $from?->value,
            'to_status'        => $toStatus->value,
            'reason'           => filled($reason) ? trim((string) $reason) : null,
            'acted_by_user_id' => $actorId,
        ]);
    }

    private function resolveMatchedUserIdByEmail(?string $email): ?int
    {
        if (! is_string($email) || trim($email) === '') {
            return Auth::id();
        }

        $normalizedEmail = Str::lower(trim($email));

        $matchedUserId = User::query()
            ->whereRaw('LOWER(email) = ?', [$normalizedEmail])
            ->value('id');

        return is_numeric($matchedUserId)
            ? (int) $matchedUserId
            : Auth::id();
    }

    private function syncBusinessEntitySnapshot(): void
    {
        if (is_numeric($this->division_id)) {
            $division = Division::query()
                ->whereKey((int) $this->division_id)
                ->first();

            if ($division) {
                $this->company_id = $division->company_id;

                return;
            }

            $this->division_id = null;
        }

        if (! is_numeric($this->company_id)) {
            $this->company_id = null;

            return;
        }

        $company = Company::query()
            ->whereKey((int) $this->company_id)
            ->first();

        if (! $company) {
            $this->company_id = null;

            return;
        }

        $this->company_id = $company->getKey();
    }

    private function syncDivisionSnapshot(): void
    {
        if (! is_numeric($this->division_id)) {
            $this->division_id = null;

            return;
        }

        $division = Division::query()
            ->whereKey((int) $this->division_id)
            ->first();

        if (! $division) {
            $this->division_id = null;

            return;
        }

        $this->divisi = $division->name;
    }

    private function resolveDefaultPipelineId(): int
    {
        $configuredPipelineId = config('rekrutmen.default_pipeline_id');

        if (is_numeric($configuredPipelineId)) {
            $configuredPipeline = RekrutmenPipeline::query()
                ->whereKey((int) $configuredPipelineId)
                ->value('id');

            if (is_numeric($configuredPipeline)) {
                return (int) $configuredPipeline;
            }
        }

        $configuredPipelineName = config('rekrutmen.default_pipeline_name');

        if (is_string($configuredPipelineName) && $configuredPipelineName !== '') {
            $configuredPipeline = RekrutmenPipeline::query()
                ->where('name', $configuredPipelineName)
                ->value('id');

            if (is_numeric($configuredPipeline)) {
                return (int) $configuredPipeline;
            }
        }

        $fallbackPipelineId = RekrutmenPipeline::query()
            ->orderBy('id')
            ->value('id');

        if (is_numeric($fallbackPipelineId)) {
            return (int) $fallbackPipelineId;
        }

        throw new RuntimeException(
            __('rekrutmen::filament/resources/request-man-power.errors.default_pipeline_not_configured')
        );
    }

    private function resolveAvailableJobPostingSlug(string $baseSlug, ?int $ignoreJobPostingId = null): string
    {
        $slug = $baseSlug;
        $suffix = 2;

        while (
            JobPosting::query()
                ->withTrashed()
                ->where('slug', $slug)
                ->when($ignoreJobPostingId, fn (Builder $query) => $query->whereKeyNot($ignoreJobPostingId))
                ->exists()
        ) {
            $slug = $baseSlug.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }

    private function unpublishLinkedJobPosting(): void
    {
        $jobPosting = $this->jobPosting()->first();

        if (! $jobPosting) {
            return;
        }

        if ($this->jobPostingHasOtherApprovedRequests($jobPosting)) {
            return;
        }

        $jobPosting->update([
            'is_published' => false,
        ]);
    }

    private function jobPostingHasOtherApprovedRequests(JobPosting $jobPosting): bool
    {
        return $jobPosting->requestManPowers()
            ->whereKeyNot($this->getKey())
            ->whereNull($this->qualifyColumn('deleted_at'))
            ->where('status', RequestManPowerStatus::APPROVED->value)
            ->exists();
    }
}

