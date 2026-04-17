<?php

namespace Cesa\Rekrutmen\Models;

use Cesa\Rekrutmen\Enums\ActivityEntryResult;
use Cesa\Rekrutmen\Enums\JobApplicationGender;
use Cesa\Rekrutmen\Enums\JobApplicationMaritalStatus;
use Cesa\Rekrutmen\Enums\JobApplicationStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Relaticle\Flowforge\Services\DecimalPosition;
use Throwable;

class JobApplication extends Model
{
    use HasFactory, SoftDeletes;

    public const RESUME_DIRECTORY = 'rekrutmen/cv';

    public const PHOTO_DIRECTORY = 'rekrutmen/photos';

    /**
     * @var array<string, ?string>
     */
    protected array $originalAttachmentPaths = [];

    /**
     * @var array{job_posting_id: int, active_email: string}|null
     */
    protected ?array $originalActiveEmailOwnership = null;

    protected $table = 'rekrutmen_job_applications';

    protected $fillable = [
        'job_posting_id',
        'current_stage_id',
        'source',
        'position',
        'full_name',
        'gender',
        'birth_date',
        'marital_status',
        'address_ktp',
        'address_domicile',
        'whatsapp_number',
        'active_phone',
        'emergency_contact_name',
        'emergency_contact_relation',
        'emergency_contact_phone',
        'email',
        'source',
        'photo_path',
        'resume_path',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'gender'         => JobApplicationGender::class,
            'birth_date'     => 'date',
            'marital_status' => JobApplicationMaritalStatus::class,
            'status'         => JobApplicationStatus::class,
            'position'       => 'decimal:10',
            'created_at'     => 'datetime',
            'updated_at'     => 'datetime',
            'deleted_at'     => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (JobApplication $application): void {
            $application->normalizeTransactionalInput();
            $application->snapshotOriginalActiveEmailOwnership();
            $application->syncActiveEmail();
            $application->assertActiveEmailIsUniqueForJobPosting();
            $application->ensurePipelineStageIntegrity();
            $application->ensureBoardPosition();
            $application->snapshotOriginalAttachmentPaths();
            $application->prepareManagedAttachmentPathForPersistence('resume_path', self::RESUME_DIRECTORY, 'CV');
            $application->prepareManagedAttachmentPathForPersistence('photo_path', self::PHOTO_DIRECTORY, 'PHOTO');
        });

        static::saved(function (JobApplication $application): void {
            if ($application->wasRecentlyCreated) {
                $application->syncManagedAttachmentPath('resume_path', self::RESUME_DIRECTORY, 'CV');
                $application->syncManagedAttachmentPath('photo_path', self::PHOTO_DIRECTORY, 'PHOTO');
            }

            $application->deleteRemovedAttachmentFile('resume_path');
            $application->deleteRemovedAttachmentFile('photo_path');
            $application->reassignOriginalActiveEmailIfNeeded();
        });

        static::created(function (JobApplication $application): void {
            $application->ensureInitialHistory();
        });

        static::deleted(function (JobApplication $application): void {
            $normalizedEmail = $application->normalizeEmail($application->getRawOriginal('email') ?? $application->email);

            if (! $application->isForceDeleting()) {
                static::query()
                    ->withoutGlobalScopes()
                    ->whereKey($application->getKey())
                    ->update(['active_email' => null]);

                $application->active_email = null;
            }

            $application->reassignActiveEmailToCanonicalPeer($normalizedEmail);
        });

        static::restored(function (JobApplication $application): void {
            $application->syncActiveEmail();

            static::query()
                ->withoutGlobalScopes()
                ->whereKey($application->getKey())
                ->update(['active_email' => $application->active_email]);
        });

        static::forceDeleted(function (JobApplication $application): void {
            $application->deleteManagedFile($application->normalizeManagedFilePath(
                $application->getRawOriginal('resume_path') ?? $application->resume_path
            ));
            $application->deleteManagedFile($application->normalizeManagedFilePath(
                $application->getRawOriginal('photo_path') ?? $application->photo_path
            ));
        });
    }

    public function jobPosting(): BelongsTo
    {
        return $this->belongsTo(JobPosting::class, 'job_posting_id')->withTrashed();
    }

    public function currentStage(): BelongsTo
    {
        return $this->belongsTo(RekrutmenStage::class, 'current_stage_id')->withTrashed();
    }

    public function histories(): HasMany
    {
        return $this->hasMany(JobApplicationHistory::class, 'job_application_id')->latest();
    }

    public function sendSubmittedNotification(): void
    {
        if (blank($this->email)) {
            return;
        }

        try {
            NotificationFacade::route('mail', $this->email)
                ->notify(new JobApplicationSubmittedNotification($this->fresh(['jobPosting', 'currentStage']) ?? $this));
        } catch (Throwable $exception) {
            Log::error('Failed to send job application submitted notification.', [
                'job_application_id' => $this->getKey(),
                'email'              => $this->email,
                'exception'          => $exception,
            ]);
        }
    }

    public function isTerminalStatus(): bool
    {
        return in_array($this->status, [
            JobApplicationStatus::HIRED,
            JobApplicationStatus::REJECTED,
            JobApplicationStatus::WITHDRAWN,
        ], true);
    }

    public static function resolveInitialStageIdForJobPostingId(mixed $jobPostingId): ?int
    {
        if (! is_numeric($jobPostingId)) {
            return null;
        }

        $jobPosting = JobPosting::query()->find((int) $jobPostingId);

        if (! $jobPosting?->rekrutmen_pipeline_id) {
            return null;
        }

        return RekrutmenStage::query()
            ->where('rekrutmen_pipeline_id', $jobPosting->rekrutmen_pipeline_id)
            ->orderBy('order_column')
            ->value('id');
    }

    public function stageBelongsToCurrentPipeline(?int $stageId): bool
    {
        $pipelineId = $this->resolvePipelineIdForCurrentJobPosting();

        if (! $pipelineId) {
            return $stageId === null;
        }

        if ($stageId === null) {
            return false;
        }

        return RekrutmenStage::query()
            ->whereKey($stageId)
            ->where('rekrutmen_pipeline_id', $pipelineId)
            ->exists();
    }

    public function syncCurrentStageToJobPosting(?int $performedBy = null, ?string $notes = null): void
    {
        $targetStageId = self::resolveInitialStageIdForJobPostingId($this->job_posting_id);

        if ($targetStageId === $this->current_stage_id) {
            return;
        }

        $fromStageId = $this->current_stage_id;

        $this->update([
            'current_stage_id' => $targetStageId,
            'position'         => $this->nextPositionForStage($targetStageId),
        ]);

        $this->recordHistory(
            $fromStageId,
            $targetStageId,
            $this->status ?? JobApplicationStatus::IN_PROGRESS,
            $notes ?? __('rekrutmen::filament/resources/job-application.workflow_notes.stage_synced'),
            $performedBy,
        );
    }

    public function transitionToStage(int $toStageId, ?string $notes = null, ?int $performedBy = null): void
    {
        if ($this->isTerminalStatus()) {
            throw new InvalidArgumentException(__('rekrutmen::filament/resources/job-application.workflow_errors.terminal_stage_locked'));
        }

        if (! $this->stageBelongsToCurrentPipeline($toStageId)) {
            throw new InvalidArgumentException(__('rekrutmen::filament/resources/job-application.workflow_errors.invalid_stage'));
        }

        if ($toStageId === $this->current_stage_id) {
            return;
        }

        $fromStageId = $this->current_stage_id;

        $this->update([
            'current_stage_id' => $toStageId,
            'position'         => $this->nextPositionForStage($toStageId),
        ]);

        $this->recordHistory(
            $fromStageId,
            $toStageId,
            $this->status ?? JobApplicationStatus::IN_PROGRESS,
            $notes ?? __('rekrutmen::filament/resources/job-application.workflow_notes.stage_changed'),
            $performedBy,
        );
    }

    public function markAsHired(?string $notes = null, ?int $performedBy = null): void
    {
        $this->assertDecisionNotesProvided($notes);

        $this->changeStatus(
            JobApplicationStatus::HIRED,
            $notes,
            $performedBy,
            $this->resolveTerminalStageIdForStatus(JobApplicationStatus::HIRED),
        );
    }

    public function markAsRejected(?string $notes = null, ?int $performedBy = null): void
    {
        $this->assertDecisionNotesProvided($notes);

        $this->changeStatus(
            JobApplicationStatus::REJECTED,
            $notes,
            $performedBy,
        );
    }

    public function changeStatus(
        JobApplicationStatus $status,
        ?string $notes = null,
        ?int $performedBy = null,
        ?int $toStageId = null,
    ): void {
        if ($toStageId !== null && ! $this->stageBelongsToCurrentPipeline($toStageId)) {
            $toStageId = null;
        }

        $targetStageId = $toStageId ?? $this->current_stage_id;

        if ($this->status === $status && $targetStageId === $this->current_stage_id) {
            return;
        }

        $currentStageId = $this->current_stage_id;

        $attributes = [
            'status' => $status,
        ];

        if ($targetStageId !== $this->current_stage_id) {
            $attributes['current_stage_id'] = $targetStageId;
            $attributes['position'] = $this->nextPositionForStage($targetStageId);
        }

        $this->update($attributes);

        $this->recordHistory(
            $currentStageId,
            $targetStageId,
            $status,
            $notes,
            $performedBy,
        );
    }

    public function ensureInitialHistory(?string $notes = null, ?int $performedBy = null): void
    {
        $status = $this->status ?? JobApplicationStatus::IN_PROGRESS;

        if ($this->current_stage_id === null || $this->histories()->exists() || $status !== JobApplicationStatus::IN_PROGRESS) {
            return;
        }

        $this->recordHistory(
            null,
            $this->current_stage_id,
            $status,
            $notes ?? __('rekrutmen::filament/resources/job-application.workflow_notes.submitted'),
            $performedBy,
        );
    }

    public static function resumeDisk(): string
    {
        $disk = config('filament.default_filesystem_disk', config('filesystems.default', 'local'));

        return is_string($disk) && $disk !== '' ? $disk : 'local';
    }

    /**
     * Record a batch activity (e.g., interview, screening) for multiple candidates.
     *
     * This is the single entry point for HR to record batch activities.
     * It creates JobApplicationHistory entries AND moves candidates to the next stage
     * or rejects them based on the result.
     *
     * @param  array{job_application_id: int, result: string, notes?: string}[]  $entries
     * @return string The activity_group_id
     */
    public static function recordBatchActivity(
        int $jobPostingId,
        int $stageId,
        string $activityDate,
        array $entries,
        ?int $performedBy,
    ): string {
        $activityGroupId = (string) Str::uuid();

        $stage = RekrutmenStage::query()
            ->whereKey($stageId)
            ->whereHas('pipeline.jobPostings', fn (Builder $query) => $query->whereKey($jobPostingId))
            ->first();

        if (! $stage) {
            throw new InvalidArgumentException(__('rekrutmen::filament/resources/activity-log.errors.invalid_stage'));
        }

        $applicationIds = collect($entries)
            ->pluck('job_application_id')
            ->filter(fn (mixed $id): bool => is_numeric($id))
            ->map(fn (mixed $id): int => (int) $id)
            ->unique()
            ->values();

        $applications = static::query()
            ->whereIn('id', $applicationIds)
            ->where('job_posting_id', $jobPostingId)
            ->with('jobPosting')
            ->get()
            ->keyBy('id');

        if ($applications->count() !== $applicationIds->count()) {
            throw new InvalidArgumentException(__('rekrutmen::filament/resources/activity-log.errors.invalid_candidates'));
        }

        $activityType = $stage->activityKey();
        $activityTitle = self::generateBatchActivityTitle($stage->activityLabel(), $activityDate);

        DB::transaction(function () use ($activityGroupId, $stageId, $activityType, $activityDate, $activityTitle, $entries, $performedBy, $applications): void {
            foreach ($entries as $entry) {
                $application = $applications->get((int) $entry['job_application_id']);

                if (! $application) {
                    continue;
                }

                if ($application->isTerminalStatus()) {
                    continue;
                }

                if ($application->current_stage_id !== $stageId) {
                    throw new InvalidArgumentException(__('rekrutmen::filament/resources/activity-log.errors.invalid_candidates'));
                }

                $fromStageId = $application->current_stage_id;
                $resultValue = $entry['result'] ?? null;
                $result = $resultValue instanceof ActivityEntryResult
                    ? $resultValue
                    : ActivityEntryResult::tryFrom($resultValue) ?? ActivityEntryResult::PENDING;
                $entryNotes = $entry['notes'] ?? null;

                $application->histories()->create([
                    'from_stage_id'     => $fromStageId,
                    'to_stage_id'       => $stageId,
                    'activity_type'     => $activityType,
                    'activity_date'     => $activityDate,
                    'result'            => $result,
                    'activity_title'    => $activityTitle,
                    'activity_group_id' => $activityGroupId,
                    'status'            => $application->status,
                    'notes'             => $entryNotes,
                    'performed_by'      => $performedBy,
                ]);

                match ($result) {
                    ActivityEntryResult::PASSED  => $application->transitionToNextStage($stageId, $entryNotes, $performedBy),
                    ActivityEntryResult::FAILED  => $application->markAsRejected($entryNotes, $performedBy),
                    ActivityEntryResult::PENDING => null,
                };
            }
        });

        return $activityGroupId;
    }

    public static function generateBatchActivityTitle(
        string $stageName,
        string $activityDate,
    ): string {
        $formattedDate = Carbon::parse($activityDate)->translatedFormat('d M Y');

        return sprintf('%s (%s)', $stageName, $formattedDate);
    }

    /**
     * Move candidate to the next stage in the pipeline after the given stage.
     * If the candidate is already at or past the given stage, no action is taken.
     */
    public function transitionToNextStage(int $completedStageId, ?string $notes = null, ?int $performedBy = null): void
    {
        if (! $this->jobPosting?->rekrutmen_pipeline_id) {
            return;
        }

        $currentStageOrder = RekrutmenStage::query()
            ->whereKey($completedStageId)
            ->value('order_column');

        if ($currentStageOrder === null) {
            return;
        }

        $nextStage = RekrutmenStage::query()
            ->where('rekrutmen_pipeline_id', $this->jobPosting->rekrutmen_pipeline_id)
            ->where('order_column', '>', $currentStageOrder)
            ->orderBy('order_column')
            ->first();

        if ($nextStage) {
            $this->transitionToStage($nextStage->id, $notes ?? __('rekrutmen::filament/resources/job-application.workflow_notes.stage_changed'), $performedBy);
        }
    }

    protected function normalizeTransactionalInput(): void
    {
        $this->full_name = $this->normalizeUppercaseText($this->full_name);
        $this->email = $this->normalizeEmail($this->email);
        $this->address_ktp = $this->normalizeUppercaseText($this->address_ktp);
        $this->address_domicile = $this->normalizeUppercaseText($this->address_domicile);
        $this->whatsapp_number = $this->normalizePhoneNumber($this->whatsapp_number);
        $this->active_phone = $this->normalizePhoneNumber($this->active_phone);
        $this->emergency_contact_name = $this->normalizeUppercaseText($this->emergency_contact_name);
        $this->emergency_contact_relation = $this->normalizeUppercaseText($this->emergency_contact_relation);
        $this->emergency_contact_phone = $this->normalizePhoneNumber($this->emergency_contact_phone);
    }

    protected function syncActiveEmail(): void
    {
        if ($this->deleted_at) {
            $this->active_email = null;

            return;
        }

        if (! is_string($this->email) || $this->email === '') {
            $this->active_email = null;

            return;
        }

        if ($this->shouldTreatAsLegacyDuplicateRecord()) {
            $this->active_email = $this->shouldClaimActiveEmailForLegacyRecord()
                ? $this->email
                : null;

            return;
        }

        $this->active_email = $this->email;
    }

    protected function snapshotOriginalActiveEmailOwnership(): void
    {
        if (! $this->exists) {
            $this->originalActiveEmailOwnership = null;

            return;
        }

        $originalActiveEmail = $this->normalizeEmail($this->getRawOriginal('active_email'));
        $originalJobPostingId = $this->getRawOriginal('job_posting_id');

        if (! is_string($originalActiveEmail) || $originalActiveEmail === '' || ! is_numeric($originalJobPostingId)) {
            $this->originalActiveEmailOwnership = null;

            return;
        }

        $this->originalActiveEmailOwnership = [
            'job_posting_id' => (int) $originalJobPostingId,
            'active_email'   => $originalActiveEmail,
        ];
    }

    protected function shouldTreatAsLegacyDuplicateRecord(): bool
    {
        return $this->exists
            && $this->getRawOriginal('active_email') === null
            && $this->normalizeEmail($this->getRawOriginal('email')) === $this->email
            && ! (
                $this->getRawOriginal('deleted_at') !== null
                && $this->deleted_at === null
            );
    }

    protected function shouldClaimActiveEmailForLegacyRecord(): bool
    {
        if (! $this->exists || ! is_numeric($this->getKey())) {
            return false;
        }

        $peerQuery = $this->matchingActiveEmailPeerQuery();

        if ((clone $peerQuery)
            ->where('active_email', $this->email)
            ->whereKeyNot($this->getKey())
            ->exists()) {
            return false;
        }

        $canonicalPeerId = (clone $peerQuery)
            ->orderBy('id')
            ->value('id');

        return is_numeric($canonicalPeerId)
            && (int) $canonicalPeerId === (int) $this->getKey();
    }

    protected function matchingActiveEmailPeerQuery(?string $normalizedEmail = null, mixed $jobPostingId = null): Builder
    {
        $normalizedEmail ??= $this->email;
        $jobPostingId ??= $this->job_posting_id;

        $query = static::query()
            ->withoutGlobalScopes()
            ->whereNull('deleted_at');

        if (! is_numeric($jobPostingId) || ! is_string($normalizedEmail) || $normalizedEmail === '') {
            return $query->whereRaw('1 = 0');
        }

        return $query
            ->where('job_posting_id', (int) $jobPostingId)
            ->whereRaw('LOWER(TRIM(email)) = ?', [$normalizedEmail]);
    }

    protected function reassignOriginalActiveEmailIfNeeded(): void
    {
        if ($this->originalActiveEmailOwnership === null) {
            return;
        }

        $originalActiveEmailOwnership = $this->originalActiveEmailOwnership;
        $this->originalActiveEmailOwnership = null;

        if (
            $originalActiveEmailOwnership['job_posting_id'] === (int) $this->job_posting_id
            && $originalActiveEmailOwnership['active_email'] === $this->active_email
        ) {
            return;
        }

        $this->reassignActiveEmailToCanonicalPeer(
            $originalActiveEmailOwnership['active_email'],
            $originalActiveEmailOwnership['job_posting_id'],
        );
    }

    protected function reassignActiveEmailToCanonicalPeer(?string $normalizedEmail = null, mixed $jobPostingId = null): void
    {
        $normalizedEmail ??= $this->email;
        $jobPostingId ??= $this->job_posting_id;

        if (! is_string($normalizedEmail) || $normalizedEmail === '') {
            return;
        }

        $peerQuery = $this->matchingActiveEmailPeerQuery($normalizedEmail, $jobPostingId);

        if ((clone $peerQuery)->where('active_email', $normalizedEmail)->exists()) {
            return;
        }

        $canonicalPeerId = (clone $peerQuery)
            ->orderBy('id')
            ->value('id');

        if (! is_numeric($canonicalPeerId)) {
            return;
        }

        static::query()
            ->withoutGlobalScopes()
            ->whereKey((int) $canonicalPeerId)
            ->update(['active_email' => $normalizedEmail]);
    }

    protected function assertActiveEmailIsUniqueForJobPosting(): void
    {
        if (! is_numeric($this->job_posting_id) || ! is_string($this->active_email) || $this->active_email === '') {
            return;
        }

        $duplicateExists = static::query()
            ->withoutGlobalScopes()
            ->where('job_posting_id', (int) $this->job_posting_id)
            ->where('active_email', $this->active_email)
            ->whereKeyNot($this->getKey())
            ->exists();

        if ($duplicateExists) {
            throw ValidationException::withMessages([
                'email' => __('rekrutmen::api/career.validation.messages.email.unique'),
            ]);
        }
    }

    protected function ensureBoardPosition(): void
    {
        if ($this->current_stage_id === null) {
            $this->position = null;

            return;
        }

        if ($this->exists && $this->isDirty('current_stage_id') && ! $this->isDirty('position')) {
            $this->position = $this->nextPositionForStage($this->current_stage_id);

            return;
        }

        if (! $this->exists && blank($this->position)) {
            $this->position = $this->nextPositionForStage($this->current_stage_id);
        }
    }

    protected function ensurePipelineStageIntegrity(): void
    {
        $normalizedStageId = $this->resolveNormalizedStageId();

        if ($normalizedStageId === $this->current_stage_id) {
            return;
        }

        $this->current_stage_id = $normalizedStageId;
    }

    protected function resolveNormalizedStageId(): ?int
    {
        $pipelineId = $this->resolvePipelineIdForCurrentJobPosting();

        if (! $pipelineId) {
            return null;
        }

        $currentStageId = is_numeric($this->current_stage_id)
            ? (int) $this->current_stage_id
            : null;

        if ($this->status === JobApplicationStatus::HIRED) {
            $hiredStageId = $this->resolveTerminalStageIdForStatus(JobApplicationStatus::HIRED);

            if ($hiredStageId !== null) {
                return $hiredStageId;
            }
        }

        if ($currentStageId !== null && $this->stageBelongsToCurrentPipeline($currentStageId)) {
            return $currentStageId;
        }

        return self::resolveInitialStageIdForJobPostingId($this->job_posting_id);
    }

    protected function resolvePipelineIdForCurrentJobPosting(): ?int
    {
        if (! is_numeric($this->job_posting_id)) {
            return null;
        }

        $pipelineId = JobPosting::query()
            ->whereKey((int) $this->job_posting_id)
            ->value('rekrutmen_pipeline_id');

        return is_numeric($pipelineId) ? (int) $pipelineId : null;
    }

    protected function snapshotOriginalAttachmentPaths(): void
    {
        if (! $this->exists) {
            $this->originalAttachmentPaths = [];

            return;
        }

        $this->originalAttachmentPaths = [
            'resume_path' => $this->normalizeManagedFilePath($this->getRawOriginal('resume_path')),
            'photo_path'  => $this->normalizeManagedFilePath($this->getRawOriginal('photo_path')),
        ];
    }

    protected function prepareManagedAttachmentPathForPersistence(string $attribute, string $directory, string $prefix): void
    {
        if (! $this->exists) {
            return;
        }

        $path = $this->normalizeManagedFilePath($this->{$attribute});

        if (! $path) {
            return;
        }

        $renamedPath = $this->renameManagedFile(
            $path,
            $directory,
            $prefix.'-'.$this->getKey(),
        );

        if ($renamedPath !== $path) {
            $this->{$attribute} = $renamedPath;
        }
    }

    protected function syncManagedAttachmentPath(string $attribute, string $directory, string $prefix): void
    {
        $path = $this->normalizeManagedFilePath($this->{$attribute});

        if (! $path || ! $this->exists) {
            return;
        }

        $renamedPath = $this->renameManagedFile(
            $path,
            $directory,
            $prefix.'-'.$this->getKey(),
        );

        if ($renamedPath === $path) {
            return;
        }

        $this->forceFill([
            $attribute => $renamedPath,
        ]);

        $this->saveQuietly();
    }

    protected function deleteRemovedAttachmentFile(string $attribute): void
    {
        if (! array_key_exists($attribute, $this->originalAttachmentPaths)) {
            return;
        }

        $originalPath = $this->originalAttachmentPaths[$attribute];
        $currentPath = $this->normalizeManagedFilePath($this->{$attribute});

        unset($this->originalAttachmentPaths[$attribute]);

        if ($originalPath === $currentPath) {
            return;
        }

        $this->deleteManagedFile($originalPath);
    }

    protected function normalizeManagedFilePath(mixed $path): ?string
    {
        if (! is_string($path)) {
            return null;
        }

        $relativePath = ltrim(trim($path), '/');

        return $relativePath !== '' ? $relativePath : null;
    }

    protected function normalizeUppercaseText(mixed $value): ?string
    {
        $normalized = $this->normalizePlainText($value);

        if ($normalized === null) {
            return null;
        }

        return mb_strtoupper($normalized);
    }

    protected function normalizePlainText(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $normalized = preg_replace('/\s+/u', ' ', trim($value));

        if (! is_string($normalized) || $normalized === '') {
            return null;
        }

        return $normalized;
    }

    protected function normalizeEmail(mixed $value): ?string
    {
        $normalized = $this->normalizePlainText($value);

        if ($normalized === null) {
            return null;
        }

        return Str::lower($normalized);
    }

    protected function normalizePhoneNumber(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $normalized = trim($value);

        if ($normalized === '') {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $normalized);

        if (! is_string($digits) || $digits === '') {
            return null;
        }

        if (str_starts_with($digits, '62')) {
            return $digits;
        }

        if (str_starts_with($digits, '0')) {
            return '62'.substr($digits, 1);
        }

        if (str_starts_with($digits, '8')) {
            return '62'.$digits;
        }

        return $digits;
    }

    public function emergencyContactSummary(): ?string
    {
        $segments = array_values(array_filter([
            $this->emergency_contact_name,
            $this->emergency_contact_relation,
            $this->emergency_contact_phone,
        ], static fn (mixed $segment): bool => is_string($segment) && $segment !== ''));

        if ($segments === []) {
            return null;
        }

        return implode(' - ', $segments);
    }

    protected function nextPositionForStage(?int $stageId): string
    {
        if ($stageId === null) {
            return DecimalPosition::forEmptyColumn();
        }

        $lastPosition = static::query()
            ->where('current_stage_id', $stageId)
            ->whereKeyNot($this->getKey())
            ->whereNotNull('position')
            ->orderByDesc('position')
            ->value('position');

        if (! is_string($lastPosition) || $lastPosition === '') {
            return DecimalPosition::forEmptyColumn();
        }

        return DecimalPosition::after(DecimalPosition::normalize($lastPosition));
    }

    protected function renameManagedFile(string $path, string $directory, string $prefix): string
    {
        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }

        $canonicalDirectory = trim($directory, '/');
        $extension = Str::lower(pathinfo($path, PATHINFO_EXTENSION));
        $expectedPrefix = $canonicalDirectory.'/'.$prefix.'-';

        if (str_starts_with($path, $expectedPrefix)) {
            return $path;
        }

        $targetPath = $this->buildManagedResumePath($canonicalDirectory, $extension);

        if ($targetPath === $path) {
            return $path;
        }

        $disk = $this->resolveManagedFileDisk($path);

        if (! $disk) {
            return $path;
        }

        try {
            if (Storage::disk($disk)->exists($targetPath)) {
                Storage::disk($disk)->delete($targetPath);
            }

            Storage::disk($disk)->move($path, $targetPath);

            return $targetPath;
        } catch (Throwable) {
            return $path;
        }
    }

    protected function buildManagedResumePath(string $directory, string $extension): string
    {
        $positionSlug = $this->resolveResumePositionSlug();
        $prefix = trim(pathinfo($directory, PATHINFO_BASENAME)) === 'photos' ? 'PHOTO' : 'CV';
        $fileName = $prefix.'-'.$this->getKey().'-'.$positionSlug;

        if ($extension !== '') {
            $fileName .= '.'.$extension;
        }

        return trim($directory, '/').'/'.$fileName;
    }

    protected function resolveResumePositionSlug(): string
    {
        $position = $this->jobPosting?->title;
        $slug = Str::slug((string) $position);

        if ($slug === '') {
            return __('rekrutmen::filament/resources/job-application.generated.unknown_position');
        }

        return Str::limit($slug, 80, '');
    }

    protected function deleteManagedFile(?string $path): void
    {
        if (! $path || filter_var($path, FILTER_VALIDATE_URL)) {
            return;
        }

        $disk = $this->resolveManagedFileDisk($path);

        if (! $disk) {
            return;
        }

        try {
            Storage::disk($disk)->delete($path);
        } catch (Throwable) {
            return;
        }
    }

    protected function resolveManagedFileDisk(string $path): ?string
    {
        $candidateDisks = array_values(array_unique(array_filter([
            config('filament.default_filesystem_disk'),
            config('filesystems.default'),
            'public',
            'local',
        ], fn (mixed $disk): bool => is_string($disk) && $disk !== '')));

        foreach ($candidateDisks as $disk) {
            if (! config()->has("filesystems.disks.{$disk}")) {
                continue;
            }

            try {
                if (Storage::disk($disk)->exists($path)) {
                    return $disk;
                }
            } catch (Throwable) {
                continue;
            }
        }

        return null;
    }

    public function resolveAttachmentPath(string $attachment): ?string
    {
        return match ($attachment) {
            'resume' => $this->resume_path,
            'photo'  => $this->photo_path,
            default  => null,
        };
    }

    protected function recordHistory(
        ?int $fromStageId,
        ?int $toStageId,
        JobApplicationStatus $status,
        ?string $notes,
        ?int $performedBy
    ): void {
        $this->histories()->create([
            'from_stage_id' => $fromStageId,
            'to_stage_id'   => $toStageId,
            'status'        => $status,
            'notes'         => $notes,
            'performed_by'  => $performedBy,
        ]);
    }

    protected function assertDecisionNotesProvided(?string $notes): void
    {
        if (! is_string($notes) || trim($notes) === '') {
            throw new InvalidArgumentException(
                __('rekrutmen::filament/resources/job-application.workflow_errors.decision_note_required')
            );
        }
    }

    protected function resolveTerminalStageIdForStatus(JobApplicationStatus $status): ?int
    {
        $pipelineId = $this->resolvePipelineIdForCurrentJobPosting();

        if ($status !== JobApplicationStatus::HIRED || ! $pipelineId) {
            return null;
        }

        return RekrutmenStage::query()
            ->where('rekrutmen_pipeline_id', $pipelineId)
            ->whereRaw('LOWER(name) = ?', ['hired'])
            ->orderByDesc('order_column')
            ->value('id');
    }
}

class JobApplicationSubmittedNotification extends Notification
{
    public function __construct(private readonly JobApplication $jobApplication) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $position = $this->jobApplication->jobPosting?->title;

        $message = (new MailMessage)
            ->subject(
                is_string($position) && $position !== ''
                    ? __('rekrutmen::mail/job-application-submitted.subject', ['position' => $position])
                    : __('rekrutmen::mail/job-application-submitted.subject_generic')
            )
            ->view('rekrutmen::mail.job-application-submitted', [
                'heading'        => __('rekrutmen::mail/job-application-submitted.heading'),
                'greeting'       => __('rekrutmen::mail/job-application-submitted.greeting', ['name' => $this->jobApplication->full_name]),
                'body'           => __('rekrutmen::mail/job-application-submitted.body'),
                'summaryHeading' => __('rekrutmen::mail/job-application-submitted.summary_heading'),
                'summary'        => $this->buildSummary(),
                'progressUrl'    => null,
                'actionLabel'    => null,
                'footerNote'     => __('rekrutmen::mail/job-application-submitted.footer_note'),
            ]);

        $mailer = config('rekrutmen.mail.job_application.mailer');

        if (is_string($mailer) && $mailer !== '') {
            $message->mailer($mailer);
        }

        $fromAddress = config('rekrutmen.mail.job_application.from.address');
        $fromName = config('rekrutmen.mail.job_application.from.name');

        if (is_string($fromAddress) && $fromAddress !== '') {
            $message->from($fromAddress, is_string($fromName) && $fromName !== '' ? $fromName : null);
        }

        $replyToAddress = config('rekrutmen.mail.job_application.reply_to.address');
        $replyToName = config('rekrutmen.mail.job_application.reply_to.name');

        if (is_string($replyToAddress) && $replyToAddress !== '') {
            $message->replyTo($replyToAddress, is_string($replyToName) && $replyToName !== '' ? $replyToName : null);
        }

        return $message;
    }

    /**
     * @return array<int, array{label: string, value: string}>
     */
    private function buildSummary(): array
    {
        return [
            [
                'label' => __('rekrutmen::mail/job-application-submitted.summary_fields.application_id'),
                'value' => (string) $this->jobApplication->getKey(),
            ],
            [
                'label' => __('rekrutmen::mail/job-application-submitted.summary_fields.submission_date'),
                'value' => $this->jobApplication->created_at?->translatedFormat('d F Y H:i') ?? '-',
            ],
            [
                'label' => __('rekrutmen::mail/job-application-submitted.summary_fields.position'),
                'value' => $this->jobApplication->jobPosting?->title ?? '-',
            ],
        ];
    }
}
