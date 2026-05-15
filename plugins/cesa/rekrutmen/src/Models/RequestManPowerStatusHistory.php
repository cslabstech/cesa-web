<?php

namespace Cesa\Rekrutmen\Models;

use Cesa\Rekrutmen\Enums\RequestManPowerStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Security\Models\User;
use Webkul\Security\Traits\HasNullableCreator;

class RequestManPowerStatusHistory extends Model
{
    use HasNullableCreator;

    protected $table = 'rekrutmen_request_man_power_status_histories';

    protected $fillable = [
        'request_man_power_id',
        'from_status',
        'to_status',
        'reason',
        'acted_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'from_status' => RequestManPowerStatus::class,
            'to_status'   => RequestManPowerStatus::class,
            'created_at'  => 'datetime',
            'updated_at'  => 'datetime',
        ];
    }

    public function requestManPower(): BelongsTo
    {
        return $this->belongsTo(RequestManPower::class, 'request_man_power_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acted_by_user_id')->withTrashed();
    }
}
