<?php

namespace Cesa\Padelnis\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Security\Traits\HasNullableCreator;

class ReservationSlot extends Model
{
    use HasNullableCreator;

    public $timestamps = false;

    protected $table = 'padelnis_reservation_slots';

    protected $fillable = [
        'active_slot_key',
    ];

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }
}
