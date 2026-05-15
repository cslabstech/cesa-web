<?php

namespace Cesa\Presensi\Policies;

use Cesa\Presensi\Models\Office;
use Illuminate\Auth\Access\HandlesAuthorization;
use Webkul\Security\Models\User;
use Webkul\Security\Traits\HasScopedPermissions;

class OfficePolicy
{
    use HandlesAuthorization, HasScopedPermissions;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_presensi_office');
    }

    public function view(User $user, Office $office): bool
    {
        return $user->can('view_presensi_office')
            && $this->hasAccess($user, $office, 'creator');
    }

    public function create(User $user): bool
    {
        return $user->can('create_presensi_office');
    }

    public function update(User $user, Office $office): bool
    {
        return $user->can('update_presensi_office')
            && $this->hasAccess($user, $office, 'creator');
    }

    public function delete(User $user, Office $office): bool
    {
        return $user->can('delete_presensi_office')
            && $this->hasAccess($user, $office, 'creator');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_presensi_office');
    }
}
