<?php

namespace Cesa\Helpdesk\Policies;

use Cesa\Helpdesk\Models\Unit;
use Illuminate\Auth\Access\HandlesAuthorization;
use Webkul\Security\Models\User;
use Webkul\Security\Traits\HasScopedPermissions;

class UnitPolicy
{
    use HandlesAuthorization, HasScopedPermissions;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_helpdesk_unit');
    }

    public function view(User $user, Unit $unit): bool
    {
        return $user->can('view_helpdesk_unit')
            && $this->hasAccess($user, $unit, 'creator');
    }

    public function create(User $user): bool
    {
        return $user->can('create_helpdesk_unit');
    }

    public function update(User $user, Unit $unit): bool
    {
        return $user->can('update_helpdesk_unit')
            && $this->hasAccess($user, $unit, 'creator');
    }

    public function delete(User $user, Unit $unit): bool
    {
        return $user->can('delete_helpdesk_unit')
            && $this->hasAccess($user, $unit, 'creator');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_helpdesk_unit');
    }

    public function forceDelete(User $user, Unit $unit): bool
    {
        return $user->can('force_delete_helpdesk_unit')
            && $this->hasAccess($user, $unit, 'creator');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_helpdesk_unit');
    }

    public function restore(User $user, Unit $unit): bool
    {
        return $user->can('restore_helpdesk_unit')
            && $this->hasAccess($user, $unit, 'creator');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_helpdesk_unit');
    }
}
