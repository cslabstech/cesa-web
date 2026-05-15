<?php

namespace Cesa\Kepegawaian\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Security\Traits\HasNullableCreator;
use Webkul\Support\Models\ActivityPlan as BaseActivityPlan;

class ActivityPlan extends BaseActivityPlan
{
    use HasNullableCreator;

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }
}
