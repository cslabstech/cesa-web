<?php

namespace Cesa\Rekrutmen\Models;

use Cesa\Rekrutmen\Enums\ActivityEntryResult;
use Cesa\Rekrutmen\Enums\JobApplicationStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Webkul\Security\Models\User;

class JobApplicationHistory extends Model
{
    use HasFactory;

    protected $table = 'rekrutmen_job_application_histories';

    protected $fillable = [
        'job_application_id',
        'from_stage_id',
        'to_stage_id',
        'activity_type',
        'activity_date',
        'result',
        'activity_title',
        'activity_group_id',
        'status',
        'notes',
        'performed_by',
    ];

    protected $casts = [
        'status'        => JobApplicationStatus::class,
        'activity_date' => 'date',
        'result'        => ActivityEntryResult::class,
        'created_at'    => 'datetime',
        'updated_at'    => 'datetime',
    ];

    public function jobApplication(): BelongsTo
    {
        return $this->belongsTo(JobApplication::class, 'job_application_id')->withTrashed();
    }

    public function fromStage(): BelongsTo
    {
        return $this->belongsTo(RekrutmenStage::class, 'from_stage_id')->withTrashed();
    }

    public function toStage(): BelongsTo
    {
        return $this->belongsTo(RekrutmenStage::class, 'to_stage_id')->withTrashed();
    }

    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by')->withTrashed();
    }

    public function isBatchActivity(): bool
    {
        return ! is_null($this->activity_group_id);
    }

    public function activityKey(): ?string
    {
        if (! is_string($this->activity_type) || $this->activity_type === '') {
            return $this->activityStage()?->activityKey();
        }

        return $this->activity_type;
    }

    public function activityLabel(): string
    {
        if ($this->isBatchActivity() && $this->fromStage) {
            return $this->fromStage->activityLabel();
        }

        if (! is_string($this->activity_type) || $this->activity_type === '') {
            return $this->activityStage()?->activityLabel() ?? '-';
        }

        return Str::headline($this->activity_type);
    }

    public function activityColor(): string|array|null
    {
        return $this->activityStage()?->activityColor() ?? 'gray';
    }

    public function activityStatusLabel(): ?string
    {
        return $this->result?->getLabel() ?? $this->status?->getLabel();
    }

    public function activityStatusColor(): string|array|null
    {
        return $this->result?->getColor() ?? $this->status?->getColor();
    }

    public function activityStage(): ?RekrutmenStage
    {
        if ($this->isBatchActivity() && $this->fromStage) {
            return $this->fromStage;
        }

        return $this->toStage;
    }
}
