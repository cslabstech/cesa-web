<?php

namespace Cesa\Rekrutmen\Models;

use Carbon\CarbonInterface;
use Cesa\Rekrutmen\Enums\RequestManPowerApprovalStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Security\Models\User;
use Webkul\Security\Traits\HasNullableCreator;

class RequestManPowerApproval extends Model
{
    use HasNullableCreator;

    protected $table = 'rekrutmen_request_man_power_approvals';

    protected $fillable = [
        'request_man_power_id',
        'approver_id',
        'approver_name',
        'approver_email',
        'approver_title',
        'step_order',
        'status',
        'action_token',
        'action_expires_at',
        'notified_at',
        'acted_at',
        'notes',
        'acted_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'step_order'        => 'integer',
            'status'            => RequestManPowerApprovalStatus::class,
            'action_expires_at' => 'datetime',
            'notified_at'       => 'datetime',
            'acted_at'          => 'datetime',
            'created_at'        => 'datetime',
            'updated_at'        => 'datetime',
        ];
    }

    public function requestManPower(): BelongsTo
    {
        return $this->belongsTo(RequestManPower::class, 'request_man_power_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(Approver::class, 'approver_id')->withTrashed();
    }

    public function actedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acted_by_user_id')->withTrashed();
    }

    public function buildApprovalUrl(): string
    {
        return route('rekrutmen.public.request-man-power.approval', [
            'token' => $this->action_token,
        ]);
    }

    public function hasExpiredActionLink(): bool
    {
        $expiresAt = $this->action_expires_at;

        return $expiresAt instanceof CarbonInterface
            ? $expiresAt->isPast()
            : true;
    }

    public function isPending(): bool
    {
        return $this->status === RequestManPowerApprovalStatus::PENDING;
    }
}
