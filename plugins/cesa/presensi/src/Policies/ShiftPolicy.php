<?php

namespace Cesa\Presensi\Policies;

use Cesa\Presensi\Models\Shift;
use Illuminate\Auth\Access\HandlesAuthorization;
use Webkul\Security\Models\User;
use Webkul\Security\Traits\HasScopedPermissions;

class ShiftPolicy
{
    use HandlesAuthorization, HasScopedPermissions;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_presensi_shift');
    }

    public function view(User $user, Shift $shift): bool
    {
        return $user->can('view_presensi_shift')
            && $this->hasAccess($user, $shift, 'creator');
    }

    public function create(User $user): bool
    {
        return $user->can('create_presensi_shift');
    }

    public function update(User $user, Shift $shift): bool
    {
        return $user->can('update_presensi_shift')
            && $this->hasAccess($user, $shift, 'creator');
    }

    public function delete(User $user, Shift $shift): bool
    {
        return $user->can('delete_presensi_shift')
            && $this->hasAccess($user, $shift, 'creator');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_presensi_shift');
    }
}
