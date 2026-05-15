<?php

namespace Cesa\Rekrutmen\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Webkul\Security\Traits\HasNullableCreator;
use Webkul\Support\Models\Company;

class Approver extends Model
{
    use HasFactory, HasNullableCreator, SoftDeletes;

    protected $table = 'rekrutmen_approvers';

    protected $fillable = [
        'name',
        'email',
        'phone',
        'title',
        'company_id',
        'division_id',
        'approval_order',
        'divisi',
        'is_active',
        'creator_id',
    ];

    protected function casts(): array
    {
        return [
            'company_id'     => 'integer',
            'division_id'    => 'integer',
            'approval_order' => 'integer',
            'is_active'      => 'boolean',
            'created_at'     => 'datetime',
            'updated_at'     => 'datetime',
            'deleted_at'     => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $approver): void {
            $approver->syncDepartmentScopeSnapshot();
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id')->withTrashed();
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class, 'division_id')->withTrashed();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->orderBy('approval_order')
            ->orderBy('name');
    }

    public function scopeMatchingRequest(Builder $query, RequestManPower $request): Builder
    {
        if ($request->division_id) {
            return $query
                ->active()
                ->where(function (Builder $builder) use ($request): void {
                    $builder->whereNull('company_id')
                        ->orWhere('company_id', $request->company_id);
                })
                ->where(function (Builder $builder) use ($request): void {
                    $builder->whereNull('division_id')
                        ->orWhere('division_id', $request->division_id);
                });
        }

        $normalizedDivision = $request->normalizedDivision();

        $query->active();

        if ($request->company_id) {
            $query->where(function (Builder $builder) use ($request): void {
                $builder->whereNull('company_id')
                    ->orWhere('company_id', $request->company_id);
            });
        } else {
            $query->whereNull('company_id');
        }

        if ($normalizedDivision === null) {
            return $query->whereNull('divisi');
        }

        return $query->where(function (Builder $builder) use ($normalizedDivision): void {
            $builder->whereNull('division_id')
                ->whereNull('divisi')
                ->orWhereRaw('LOWER(TRIM(divisi)) = ?', [$normalizedDivision]);
        });
    }

    private function syncDepartmentScopeSnapshot(): void
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

        $this->division_id = $division->getKey();
        $this->company_id = $division->company_id;
        $this->divisi = $division->name;
    }
}
