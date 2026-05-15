<?php

namespace Cesa\ExitClearance\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Webkul\Security\Traits\HasNullableCreator;

class Approver extends Model
{
    use HasFactory, HasNullableCreator, SoftDeletes;

    protected $table = 'exit_clearance_approvers';

    protected $fillable = [
        'name',
        'email',
        'phone',
        'title',
        'creator_id',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected function getAssignmentColumn(): ?string
    {
        return null;
    }

    public function departments(): BelongsToMany
    {
        return $this->belongsToMany(Department::class, 'exit_clearance_department_approver', 'approver_id', 'department_id')
            ->withTrashed();
    }

    public function requests(): BelongsToMany
    {
        return $this->belongsToMany(Request::class, 'exit_clearance_request_approver', 'approver_id', 'request_id')
            ->withPivot(['approved_at', 'notes', 'status'])
            ->withTimestamps()
            ->withTrashed();
    }
}
