<?php

namespace Cesa\Rekrutmen\Policies;

use Cesa\Rekrutmen\Models\Division;
use Illuminate\Auth\Access\HandlesAuthorization;
use Webkul\Security\Models\User;
use Webkul\Security\Traits\HasScopedPermissions;

class DivisionPolicy
{
    use HandlesAuthorization, HasScopedPermissions;

    public function viewAny(User $user): bool
    {
        return $this->canManageDivision($user, 'view_any_rekrutmen_division', 'view_any_rekrutmen_request::man::power');
    }

    public function view(User $user, Division $division): bool
    {
        return $this->canManageDivision($user, 'view_rekrutmen_division', 'view_rekrutmen_request::man::power')
            && $this->hasAccess($user, $division, 'creator');
    }

    public function create(User $user): bool
    {
        return $this->canManageDivision($user, 'create_rekrutmen_division', 'create_rekrutmen_request::man::power');
    }

    public function update(User $user, Division $division): bool
    {
        return $this->canManageDivision($user, 'update_rekrutmen_division', 'update_rekrutmen_request::man::power')
            && $this->hasAccess($user, $division, 'creator');
    }

    public function delete(User $user, Division $division): bool
    {
        return $this->canManageDivision($user, 'delete_rekrutmen_division', 'delete_rekrutmen_request::man::power')
            && $this->hasAccess($user, $division, 'creator');
    }

    public function deleteAny(User $user): bool
    {
        return $this->canManageDivision($user, 'delete_any_rekrutmen_division', 'delete_any_rekrutmen_request::man::power');
    }

    public function forceDelete(User $user, Division $division): bool
    {
        return $this->canManageDivision($user, 'force_delete_rekrutmen_division', 'force_delete_rekrutmen_request::man::power')
            && $this->hasAccess($user, $division, 'creator');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $this->canManageDivision($user, 'force_delete_any_rekrutmen_division', 'force_delete_any_rekrutmen_request::man::power');
    }

    public function restore(User $user, Division $division): bool
    {
        return $this->canManageDivision($user, 'restore_rekrutmen_division', 'restore_rekrutmen_request::man::power')
            && $this->hasAccess($user, $division, 'creator');
    }

    public function restoreAny(User $user): bool
    {
        return $this->canManageDivision($user, 'restore_any_rekrutmen_division', 'restore_any_rekrutmen_request::man::power');
    }

    private function canManageDivision(User $user, string $divisionPermission, string $fallbackPermission): bool
    {
        return $user->can($divisionPermission) || $user->can($fallbackPermission);
    }
}
