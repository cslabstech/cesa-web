<?php

namespace Cesa\Rekrutmen\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Webkul\Security\Traits\HasNullableCreator;

class RekrutmenStage extends Model
{
    use HasFactory, HasNullableCreator, SoftDeletes;

    public const FINAL_HIRED_STAGE_NAME = 'Hired';

    protected $table = 'rekrutmen_stages';

    protected $fillable = [
        'rekrutmen_pipeline_id',
        'name',
        'order_column',
    ];

    protected static function booted(): void
    {
        static::saving(function (RekrutmenStage $stage): void {
            $stage->reserveOrderBeforeLockedFinalStage();
            $stage->normalizeLockedFinalStage();
        });

        static::saved(function (RekrutmenStage $stage): void {
            $stage->syncLockedFinalStageToPipelineEnd();
        });

        static::deleting(function (RekrutmenStage $stage): void {
            $stage->guardLockedFinalStageDeletion();
        });
    }

    public function pipeline(): BelongsTo
    {
        return $this->belongsTo(RekrutmenPipeline::class, 'rekrutmen_pipeline_id')->withTrashed();
    }

    public function isLockedFinalStage(): bool
    {
        return static::isFinalHiredStageName($this->name)
            || static::isFinalHiredStageName($this->getRawOriginal('name'));
    }

    public static function isFinalHiredStageName(?string $name): bool
    {
        return Str::lower(trim((string) $name)) === Str::lower(static::FINAL_HIRED_STAGE_NAME);
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

    protected function normalizeLockedFinalStage(): void
    {
        if (! $this->isLockedFinalStage()) {
            return;
        }

        $this->name = static::FINAL_HIRED_STAGE_NAME;

        if (! is_numeric($this->rekrutmen_pipeline_id)) {
            return;
        }

        $duplicateExists = static::query()
            ->where('rekrutmen_pipeline_id', (int) $this->rekrutmen_pipeline_id)
            ->whereRaw('LOWER(name) = ?', [Str::lower(static::FINAL_HIRED_STAGE_NAME)])
            ->when($this->exists, fn ($query) => $query->whereKeyNot($this->getKey()))
            ->exists();

        if ($duplicateExists) {
            throw new InvalidArgumentException(
                __('rekrutmen::filament/resources/rekrutmen-pipeline.errors.duplicate_final_hired_stage')
            );
        }
    }

    protected function reserveOrderBeforeLockedFinalStage(): void
    {
        if ($this->isLockedFinalStage() || ! is_numeric($this->rekrutmen_pipeline_id) || ! is_numeric($this->order_column)) {
            return;
        }

        $finalHiredStage = static::query()
            ->where('rekrutmen_pipeline_id', (int) $this->rekrutmen_pipeline_id)
            ->whereRaw('LOWER(name) = ?', [Str::lower(static::FINAL_HIRED_STAGE_NAME)])
            ->when($this->exists, fn ($query) => $query->whereKeyNot($this->getKey()))
            ->first();

        if (! $finalHiredStage || (int) $finalHiredStage->order_column !== (int) $this->order_column) {
            return;
        }

        $targetOrder = $this->nextAvailableOrderForPipeline(
            (int) $this->rekrutmen_pipeline_id,
            ((int) static::withTrashed()
                ->where('rekrutmen_pipeline_id', (int) $this->rekrutmen_pipeline_id)
                ->max('order_column')) + 1,
            (int) $finalHiredStage->getKey(),
        );

        static::withoutEvents(function () use ($finalHiredStage, $targetOrder): void {
            static::query()
                ->whereKey($finalHiredStage->getKey())
                ->update([
                    'order_column' => $targetOrder,
                ]);
        });
    }

    protected function syncLockedFinalStageToPipelineEnd(): void
    {
        if (! is_numeric($this->rekrutmen_pipeline_id)) {
            return;
        }

        $finalHiredStage = static::query()
            ->where('rekrutmen_pipeline_id', (int) $this->rekrutmen_pipeline_id)
            ->whereRaw('LOWER(name) = ?', [Str::lower(static::FINAL_HIRED_STAGE_NAME)])
            ->orderByDesc('order_column')
            ->first();

        if (! $finalHiredStage) {
            return;
        }

        $highestNonFinalOrder = static::query()
            ->where('rekrutmen_pipeline_id', (int) $this->rekrutmen_pipeline_id)
            ->whereKeyNot($finalHiredStage->getKey())
            ->max('order_column');

        $targetOrder = $this->nextAvailableOrderForPipeline(
            (int) $this->rekrutmen_pipeline_id,
            ((int) $highestNonFinalOrder) + 1,
            (int) $finalHiredStage->getKey(),
        );

        if (
            $finalHiredStage->name === static::FINAL_HIRED_STAGE_NAME
            && (int) $finalHiredStage->order_column === $targetOrder
        ) {
            return;
        }

        static::withoutEvents(function () use ($finalHiredStage, $targetOrder): void {
            static::query()
                ->whereKey($finalHiredStage->getKey())
                ->update([
                    'name'         => static::FINAL_HIRED_STAGE_NAME,
                    'order_column' => $targetOrder,
                ]);
        });
    }

    protected function nextAvailableOrderForPipeline(int $pipelineId, int $startAt = 1, ?int $exceptStageId = null): int
    {
        $usedOrders = static::withTrashed()
            ->where('rekrutmen_pipeline_id', $pipelineId)
            ->when($exceptStageId !== null, fn ($query) => $query->whereKeyNot($exceptStageId))
            ->pluck('order_column')
            ->map(fn (mixed $order): int => (int) $order);

        $candidateOrder = max(1, $startAt);

        while ($usedOrders->contains($candidateOrder)) {
            $candidateOrder++;
        }

        return $candidateOrder;
    }

    protected function guardLockedFinalStageDeletion(): void
    {
        if (! $this->isLockedFinalStage()) {
            return;
        }

        throw new InvalidArgumentException(
            __('rekrutmen::filament/resources/rekrutmen-pipeline.errors.final_hired_stage_locked')
        );
    }
}
