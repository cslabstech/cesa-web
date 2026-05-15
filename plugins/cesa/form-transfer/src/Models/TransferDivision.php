<?php

namespace Cesa\FormTransfer\Models;

use Cesa\FormTransfer\Database\Factories\TransferDivisionFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Webkul\Security\Traits\HasNullableCreator;

class TransferDivision extends Model
{
    use HasFactory, HasNullableCreator, SoftDeletes;

    protected $table = 'form_transfer_divisions';

    protected $fillable = [
        'form_transfer_id',
        'name',
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
        static::saving(function (self $division): void {
            if (! FormTransfer::query()->internalEntry()->whereKey($division->form_transfer_id)->exists()) {
                throw ValidationException::withMessages([
                    'form_transfer_id' => __('validation.exists', [
                        'attribute' => Str::lower(
                            __('form-transfer::filament/clusters/configurations/resources/division.fields.form_transfer')
                        ),
                    ]),
                ]);
            }
        });
    }

    public function formTransfer(): BelongsTo
    {
        return $this->belongsTo(FormTransfer::class)->withTrashed();
    }

    protected static function newFactory(): Factory
    {
        return TransferDivisionFactory::new();
    }
}
