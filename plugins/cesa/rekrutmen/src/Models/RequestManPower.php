<?php

namespace Cesa\Rekrutmen\Models;

use Cesa\Rekrutmen\Enums\RequestManPowerApprovalStatus;
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
use Webkul\Support\Models\Company;

class RequestManPower extends Model
{
    use HasFactory, SoftDeletes;

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
    ];

    protected function casts(): array
    {
        return [
            'status_kebutuhan'           => StatusKebutuhan::class,
            'status'                     => RequestManPowerStatus::class,
            'company_id'                 => 'integer',
            'division_id'                => 'integer',
            'tanggal_pengajuan'          => 'date',
            'estimasi_tanggal_join'      => 'date',
            'jumlah_karyawan_dibutuhkan' => 'integer',
            'status_response_id'         => 'string',
            'created_at'                 => 'datetime',
            'updated_at'                 => 'datetime',
            'deleted_at'                 => 'datetime',
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

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id')->withTrashed();
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class, 'division_id')->withTrashed();
    }

    public function jobPosting(): HasOne
    {
        return $this->hasOne(JobPosting::class, 'request_man_power_id')->withTrashed();
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(RequestManPowerApproval::class, 'request_man_power_id')
            ->orderBy('step_order');
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

    public function scopeByTanggal(Builder $query, string $from, string $to): Builder
    {
        return $query->whereBetween('tanggal_pengajuan', [$from, $to]);
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
                        'action_token'         => $isFirstStep ? (string) Str::uuid() : null,
                        'action_expires_at'    => null,
                        'notified_at'          => null,
                        'acted_at'             => null,
                        'notes'                => null,
                        'acted_by_user_id'     => null,
                        'created_at'           => $timestamp,
                        'updated_at'           => $timestamp,
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

    public function sendApprovalRequestNotifications(): void
    {
        $this->initializeApprovalWorkflow();
        $this->notifyCurrentPendingApproval();
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
            'action_token'      => $rotateToken || blank($approval->action_token)
                ? (string) Str::uuid()
            : $approval->action_token,
            'action_expires_at' => now()->addMinutes(
                (int) config('rekrutmen.security.approval_link_expiration_minutes', 10080)
            ),
            'notified_at'       => now(),
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

        DB::transaction(function () use ($approverId): void {
            $this->createJobPostingIfMissing();

            $this->update([
                'status'      => RequestManPowerStatus::APPROVED,
                'approved_by' => $approverId,
            ]);
        });

        $this->sendStatusChangedNotification($previousStatus, RequestManPowerStatus::APPROVED);
    }

    public function rejectBy(?int $approverId = null): void
    {
        $previousStatus = $this->status;

        DB::transaction(function () use ($approverId): void {
            $this->update([
                'status'      => RequestManPowerStatus::REJECTED,
                'approved_by' => $approverId,
            ]);

            $this->unpublishLinkedJobPosting();
        });

        $this->sendStatusChangedNotification($previousStatus, RequestManPowerStatus::REJECTED);
    }

    public function markPending(): void
    {
        $previousStatus = $this->status;

        DB::transaction(function (): void {
            $this->update([
                'status'      => RequestManPowerStatus::PENDING,
                'approved_by' => null,
            ]);

            $this->initializeApprovalWorkflow(replaceExisting: true);
            $this->unpublishLinkedJobPosting();
        });

        $this->sendStatusChangedNotification($previousStatus, RequestManPowerStatus::PENDING);
        $this->notifyCurrentPendingApproval(true);
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

        $previousStatus = $this->status;
        $actedByUserId = $this->resolveMatchedUserIdByEmail($approval->approver_email);
        $nextApprovalId = null;

        DB::transaction(function () use ($approval, $notes, $actedByUserId, &$nextApprovalId): void {
            $approval->forceFill([
                'status'            => RequestManPowerApprovalStatus::APPROVED,
                'notes'             => $notes,
                'acted_at'          => now(),
                'acted_by_user_id'  => $actedByUserId,
                'action_expires_at' => now(),
            ])->save();

            $nextApproval = $this->approvals()
                ->where('step_order', '>', $approval->step_order)
                ->orderBy('step_order')
                ->first();

            if ($nextApproval) {
                $nextApproval->forceFill([
                    'status'        => RequestManPowerApprovalStatus::PENDING,
                    'action_token'  => (string) Str::uuid(),
                    'notified_at'   => null,
                ])->save();

                $nextApprovalId = $nextApproval->getKey();

                return;
            }

            $this->createJobPostingIfMissing();

            $this->update([
                'status'      => RequestManPowerStatus::APPROVED,
                'approved_by' => $actedByUserId,
            ]);
        });

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

        $previousStatus = $this->status;
        $actedByUserId = $this->resolveMatchedUserIdByEmail($approval->approver_email);

        DB::transaction(function () use ($approval, $notes, $actedByUserId): void {
            $approval->forceFill([
                'status'            => RequestManPowerApprovalStatus::REJECTED,
                'notes'             => $notes,
                'acted_at'          => now(),
                'acted_by_user_id'  => $actedByUserId,
                'action_expires_at' => now(),
            ])->save();

            $this->approvals()
                ->where('step_order', '>', $approval->step_order)
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

            $this->update([
                'status'      => RequestManPowerStatus::REJECTED,
                'approved_by' => $actedByUserId,
            ]);

            $this->unpublishLinkedJobPosting();
        });

        $this->sendStatusChangedNotification($previousStatus, RequestManPowerStatus::REJECTED);
    }

    public function createJobPostingIfMissing(): JobPosting
    {
        $existingPosting = JobPosting::query()
            ->withTrashed()
            ->where('request_man_power_id', $this->getKey())
            ->first();

        $title = trim(implode(' ', array_filter([
            $this->posisi_dibutuhkan,
            $this->lokasi_penempatan,
        ])));

        if ($title === '') {
            $title = __('rekrutmen::filament/resources/job-posting.generated.title', ['id' => $this->getKey()]);
        }

        $baseSlug = Str::slug($title);
        $baseSlug = $baseSlug !== '' ? $baseSlug : 'job-posting-'.$this->getKey();
        $pipelineId = $existingPosting?->rekrutmen_pipeline_id ?? $this->resolveDefaultPipelineId();
        $slug = $this->resolveAvailableJobPostingSlug($baseSlug, $existingPosting?->getKey());

        if ($existingPosting) {
            if ($existingPosting->trashed()) {
                $existingPosting->restore();
            }

            $existingPosting->update([
                'rekrutmen_pipeline_id' => $pipelineId,
                'title'                 => $title,
                'slug'                  => $slug,
                'description'           => $this->job_description,
                'requirements'          => $this->requirements_kualifikasi,
                'location'              => $this->lokasi_penempatan,
                'closing_date'          => $this->estimasi_tanggal_join,
            ]);

            return $existingPosting->fresh();
        }

        return JobPosting::query()->create([
            'request_man_power_id'  => $this->getKey(),
            'rekrutmen_pipeline_id' => $pipelineId,
            'title'                 => $title,
            'slug'                  => $slug,
            'description'           => $this->job_description,
            'requirements'          => $this->requirements_kualifikasi,
            'location'              => $this->lokasi_penempatan,
            'is_published'          => false,
            'closing_date'          => $this->estimasi_tanggal_join,
        ]);
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

        $jobPosting->update([
            'is_published' => false,
        ]);
    }
}

class RequestManPowerSubmittedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly RequestManPower $requestManPower)
    {
        $this->onQueue(config('rekrutmen.notifications.queue', 'notifications'));
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('rekrutmen::mail/request-man-power-submitted.subject'))
            ->action(
                __('rekrutmen::mail/request-man-power-submitted.view_progress'),
                $this->requestManPower->getPublicProgressUrl(),
            )
            ->view('rekrutmen::mail.request-man-power-submitted', [
                'request'     => $this->requestManPower,
                'summary'     => $this->buildSummary(),
                'progressUrl' => $this->requestManPower->getPublicProgressUrl(),
            ]);
    }

    /**
     * @return array<int, array{label: string, value: string}>
     */
    private function buildSummary(): array
    {
        return [
            [
                'label' => __('rekrutmen::mail/request-man-power-submitted.summary_fields.submission_id'),
                'value' => $this->requestManPower->status_response_id ?? '-',
            ],
            [
                'label' => __('rekrutmen::mail/request-man-power-submitted.summary_fields.submission_date'),
                'value' => $this->requestManPower->getTanggalPengajuanFormattedAttribute(),
            ],
            [
                'label' => __('rekrutmen::mail/request-man-power-submitted.summary_fields.applicant'),
                'value' => $this->requestManPower->nama_pengaju ?? '-',
            ],
            [
                'label' => __('rekrutmen::mail/request-man-power-submitted.summary_fields.position'),
                'value' => $this->requestManPower->posisi_dibutuhkan ?? '-',
            ],
            [
                'label' => __('rekrutmen::mail/request-man-power-submitted.summary_fields.requirement'),
                'value' => $this->requestManPower->status_kebutuhan?->getLabel() ?? '-',
            ],
            [
                'label' => __('rekrutmen::mail/request-man-power-submitted.summary_fields.division'),
                'value' => $this->requestManPower->division_name ?? '-',
            ],
            [
                'label' => __('rekrutmen::mail/request-man-power-submitted.summary_fields.business_entity'),
                'value' => $this->requestManPower->business_entity_name ?? '-',
            ],
            [
                'label' => __('rekrutmen::mail/request-man-power-submitted.summary_fields.estimated_join'),
                'value' => $this->requestManPower->getEstimasiTanggalJoinFormattedAttribute(),
            ],
        ];
    }
}

class RequestManPowerStatusChangedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly RequestManPower $requestManPower,
        private readonly ?RequestManPowerStatus $fromStatus,
        private readonly RequestManPowerStatus $toStatus,
    ) {
        $this->onQueue(config('rekrutmen.notifications.queue', 'notifications'));
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('rekrutmen::mail/request-man-power-status-changed.subject'))
            ->action(
                __('rekrutmen::mail/request-man-power-status-changed.view_progress'),
                $this->requestManPower->getPublicProgressUrl(),
            )
            ->view('rekrutmen::mail.request-man-power-status-changed', [
                'request'     => $this->requestManPower,
                'summary'     => $this->buildSummary(),
                'progressUrl' => $this->requestManPower->getPublicProgressUrl(),
            ]);
    }

    /**
     * @return array<int, array{label: string, value: string}>
     */
    private function buildSummary(): array
    {
        $summary = [
            [
                'label' => __('rekrutmen::mail/request-man-power-status-changed.summary_fields.submission_id'),
                'value' => $this->requestManPower->status_response_id ?? '-',
            ],
            [
                'label' => __('rekrutmen::mail/request-man-power-status-changed.summary_fields.applicant'),
                'value' => $this->requestManPower->nama_pengaju ?? '-',
            ],
            [
                'label' => __('rekrutmen::mail/request-man-power-status-changed.summary_fields.position'),
                'value' => $this->requestManPower->posisi_dibutuhkan ?? '-',
            ],
            [
                'label' => __('rekrutmen::mail/request-man-power-status-changed.summary_fields.latest_status'),
                'value' => $this->toStatus->getLabel(),
            ],
        ];

        if ($this->fromStatus) {
            $summary[] = [
                'label' => __('rekrutmen::mail/request-man-power-status-changed.summary_fields.previous_status'),
                'value' => $this->fromStatus->getLabel(),
            ];
        }

        $summary[] = [
            'label' => __('rekrutmen::mail/request-man-power-status-changed.summary_fields.division'),
            'value' => $this->requestManPower->division_name ?? '-',
        ];

        $summary[] = [
            'label' => __('rekrutmen::mail/request-man-power-status-changed.summary_fields.business_entity'),
            'value' => $this->requestManPower->business_entity_name ?? '-',
        ];

        return $summary;
    }
}

class RequestManPowerApprovalRequestedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly RequestManPower $requestManPower,
        private readonly RequestManPowerApproval $approval,
    ) {
        $this->onQueue(config('rekrutmen.notifications.queue', 'notifications'));
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('rekrutmen::mail/request-man-power-approval-request.subject'))
            ->action(
                __('rekrutmen::mail/request-man-power-approval-request.action'),
                $this->approval->buildApprovalUrl(),
            )
            ->view('rekrutmen::mail.request-man-power-approval-request', [
                'request'      => $this->requestManPower,
                'approverName' => $this->approval->approver_name,
                'summary'      => $this->buildSummary(),
                'actionUrl'    => $this->approval->buildApprovalUrl(),
                'progressUrl'  => $this->requestManPower->getPublicProgressUrl(),
            ]);
    }

    /**
     * @return array<int, array{label: string, value: string}>
     */
    private function buildSummary(): array
    {
        return [
            [
                'label' => __('rekrutmen::mail/request-man-power-approval-request.summary_fields.submission_id'),
                'value' => $this->requestManPower->status_response_id ?? '-',
            ],
            [
                'label' => __('rekrutmen::mail/request-man-power-approval-request.summary_fields.submission_date'),
                'value' => $this->requestManPower->getTanggalPengajuanFormattedAttribute(),
            ],
            [
                'label' => __('rekrutmen::mail/request-man-power-approval-request.summary_fields.applicant'),
                'value' => $this->requestManPower->nama_pengaju ?? '-',
            ],
            [
                'label' => __('rekrutmen::mail/request-man-power-approval-request.summary_fields.position'),
                'value' => $this->requestManPower->posisi_dibutuhkan ?? '-',
            ],
            [
                'label' => __('rekrutmen::mail/request-man-power-approval-request.summary_fields.requirement'),
                'value' => $this->requestManPower->status_kebutuhan?->getLabel() ?? '-',
            ],
            [
                'label' => __('rekrutmen::mail/request-man-power-approval-request.summary_fields.division'),
                'value' => $this->requestManPower->division_name ?? '-',
            ],
            [
                'label' => __('rekrutmen::mail/request-man-power-approval-request.summary_fields.business_entity'),
                'value' => $this->requestManPower->business_entity_name ?? '-',
            ],
            [
                'label' => __('rekrutmen::mail/request-man-power-approval-request.summary_fields.estimated_join'),
                'value' => $this->requestManPower->getEstimasiTanggalJoinFormattedAttribute(),
            ],
        ];
    }
}
