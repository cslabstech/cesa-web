<?php

namespace Cesa\FormTransfer\Models;

use Cesa\FormTransfer\Database\Factories\TransferReferenceNoteFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TransferReferenceNote extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'form_transfer_reference_notes';

    protected $fillable = [
        'form_transfer_id',
        'label',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $referenceNote): void {
            if (! FormTransfer::query()->internalEntry()->whereKey($referenceNote->form_transfer_id)->exists()) {
                throw ValidationException::withMessages([
                    'form_transfer_id' => __('validation.exists', [
                        'attribute' => Str::lower(
                            __('form-transfer::filament/clusters/configurations/resources/reference-note.fields.form_transfer')
                        ),
                    ]),
                ]);
            }
        });
    }

    protected static function newFactory(): TransferReferenceNoteFactory
    {
        return TransferReferenceNoteFactory::new();
    }

    public function formTransfer(): BelongsTo
    {
        return $this->belongsTo(FormTransfer::class)->withTrashed();
    }
}
