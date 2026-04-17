<?php

namespace Cesa\Rekrutmen\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class RekrutmenStage extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'rekrutmen_stages';

    protected $fillable = [
        'rekrutmen_pipeline_id',
        'name',
        'order_column',
    ];

    public function pipeline(): BelongsTo
    {
        return $this->belongsTo(RekrutmenPipeline::class, 'rekrutmen_pipeline_id')->withTrashed();
    }

    public function activityKey(): string
    {
        $key = Str::of($this->name)
            ->trim()
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', '_')
            ->trim('_')
            ->value();

        return $key !== '' ? $key : 'other';
    }

    public function activityLabel(): string
    {
        return (string) $this->name;
    }

    public function activityColor(): string|array|null
    {
        $normalizedStageName = Str::of($this->name)
            ->lower()
            ->squish()
            ->value();

        if (str_contains($normalizedStageName, 'screen')) {
            return 'gray';
        }

        if (
            str_contains($normalizedStageName, 'interview hr')
            || str_contains($normalizedStageName, 'hr interview')
            || (str_contains($normalizedStageName, 'interview') && str_contains($normalizedStageName, 'hr'))
        ) {
            return 'warning';
        }

        if (
            str_contains($normalizedStageName, 'interview user')
            || str_contains($normalizedStageName, 'user interview')
            || (str_contains($normalizedStageName, 'interview') && str_contains($normalizedStageName, 'user'))
        ) {
            return 'info';
        }

        if (str_contains($normalizedStageName, 'teknis') || str_contains($normalizedStageName, 'technical')) {
            return 'primary';
        }

        if (str_contains($normalizedStageName, 'psikologi') || str_contains($normalizedStageName, 'psycholog')) {
            return 'purple';
        }

        if (str_contains($normalizedStageName, 'medical')) {
            return 'success';
        }

        if (str_contains($normalizedStageName, 'reference')) {
            return 'gray';
        }

        if (str_contains($normalizedStageName, 'offer')) {
            return 'success';
        }

        if (str_contains($normalizedStageName, 'hired')) {
            return 'success';
        }

        return 'gray';
    }
}
