<?php

namespace Cesa\Rekrutmen\Models;

use Cesa\Rekrutmen\Enums\JobApplicationGender;
use Cesa\Rekrutmen\Enums\JobApplicationMaritalStatus;
use Cesa\Rekrutmen\Enums\JobApplicationStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Relaticle\Flowforge\Services\DecimalPosition;

class JobApplication extends Model
{
    use HasFactory, SoftDeletes;

    public const RESUME_DIRECTORY = 'rekrutmen/cv';

    public const PHOTO_DIRECTORY = 'rekrutmen/photos';

    /**
     * @var array<string, ?string>
     */
    protected array $originalAttachmentPaths = [];

    protected $table = 'rekrutmen_job_applications';

    protected $fillable = [
        'job_posting_id',
        'current_stage_id',
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
        $pipelineId = $this->jobPosting?->rekrutmen_pipeline_id;

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

    public function changeStatus(JobApplicationStatus $status, ?string $notes = null, ?int $performedBy = null): void
    {
        if ($this->status === $status) {
            return;
        }

        $currentStageId = $this->current_stage_id;

        $this->update([
            'status' => $status,
        ]);

        $this->recordHistory(
            $currentStageId,
            $currentStageId,
            $status,
            $notes,
            $performedBy,
        );
    }

    public static function resumeDisk(): string
    {
        $disk = config('filament.default_filesystem_disk', config('filesystems.default', 'local'));

        return is_string($disk) && $disk !== '' ? $disk : 'local';
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
        } catch (\Throwable) {
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
        } catch (\Throwable) {
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
            } catch (\Throwable) {
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
}
