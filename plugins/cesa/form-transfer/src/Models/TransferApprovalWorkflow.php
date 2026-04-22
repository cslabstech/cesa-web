<?php

namespace Cesa\FormTransfer\Models;

use Cesa\FormTransfer\Database\Factories\TransferApprovalWorkflowFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TransferApprovalWorkflow extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'form_transfer_approval_workflows';

    protected $fillable = [
        'form_transfer_id',
        'division_id',
        'name',
        'code',
        'description',
        'steps',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'steps'     => 'array',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $workflow): void {
            if (! FormTransfer::query()->internalEntry()->whereKey($workflow->form_transfer_id)->exists()) {
                throw ValidationException::withMessages([
                    'form_transfer_id' => __('validation.exists', [
                        'attribute' => Str::lower(
                            __('form-transfer::filament/clusters/configurations/resources/approval-workflow.fields.form_transfer')
                        ),
                    ]),
                ]);
            }

            $steps = collect($workflow->steps ?? [])
                ->map(function (mixed $step): mixed {
                    if (! is_array($step)) {
                        return $step;
                    }

                    if (is_string($step['default_email'] ?? null)) {
                        $step['default_email'] = trim($step['default_email']);
                    }

                    return $step;
                })
                ->all();

            foreach ($steps as $index => $step) {
                if (! is_array($step) || blank($step['default_email'] ?? null)) {
                    throw ValidationException::withMessages([
                        "steps.{$index}.default_email" => __('validation.required', [
                            'attribute' => Str::lower(__('form-transfer::filament/clusters/configurations/resources/approval-workflow.fields.step_default_email')),
                        ]),
                    ]);
                }
            }

            $workflow->steps = $steps;
        });
    }

    public function getStepCountAttribute(): int
    {
        return count($this->steps ?? []);
    }

    public function getStepSummaryAttribute(): string
    {
        return collect($this->steps ?? [])
            ->map(fn (array $step, int $index): string => $step['label'] ?? 'Step '.($index + 1))
            ->implode(' → ');
    }

    public function formTransfer(): BelongsTo
    {
        return $this->belongsTo(FormTransfer::class)->withTrashed();
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(TransferDivision::class, 'division_id')->withTrashed();
    }

    protected static function newFactory(): Factory
    {
        return TransferApprovalWorkflowFactory::new();
    }
}
