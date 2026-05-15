<?php

namespace Cesa\Payroll\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Webkul\Security\Traits\HasNullableCreator;

class PayrollPeriod extends Model
{
    use HasFactory, HasNullableCreator, SoftDeletes;

    protected $table = 'payroll_periods';

    protected $fillable = [
        'name',
        'start_date',
        'end_date',
        'status',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
    ];

    protected $attributes = [
        'status' => 'open',
    ];

    public function records(): HasMany
    {
        return $this->hasMany(PayrollRecord::class)->withTrashed();
    }
}
