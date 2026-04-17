<?php

namespace Cesa\Rekrutmen\Policies;

use Cesa\Rekrutmen\Models\Approver;
use Illuminate\Auth\Access\HandlesAuthorization;
use Webkul\Security\Models\User;

class ApproverPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $this->canManageApprover($user, 'view_any_rekrutmen_approver', 'view_any_rekrutmen_request::man::power');
    }

    public function view(User $user, Approver $approver): bool
    {
        return $this->canManageApprover($user, 'view_rekrutmen_approver', 'view_rekrutmen_request::man::power');
    }

    public function create(User $user): bool
    {
        return $this->canManageApprover($user, 'create_rekrutmen_approver', 'create_rekrutmen_request::man::power');
    }

    public function update(User $user, Approver $approver): bool
    {
        return $this->canManageApprover($user, 'update_rekrutmen_approver', 'update_rekrutmen_request::man::power');
    }

    public function delete(User $user, Approver $approver): bool
    {
        return $this->canManageApprover($user, 'delete_rekrutmen_approver', 'delete_rekrutmen_request::man::power');
    }

    public function deleteAny(User $user): bool
    {
        return $this->canManageApprover($user, 'delete_any_rekrutmen_approver', 'delete_any_rekrutmen_request::man::power');
    }

    public function forceDelete(User $user, Approver $approver): bool
    {
        return $this->canManageApprover($user, 'force_delete_rekrutmen_approver', 'force_delete_rekrutmen_request::man::power');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $this->canManageApprover($user, 'force_delete_any_rekrutmen_approver', 'force_delete_any_rekrutmen_request::man::power');
    }

    public function restore(User $user, Approver $approver): bool
    {
        return $this->canManageApprover($user, 'restore_rekrutmen_approver', 'restore_rekrutmen_request::man::power');
    }

    public function restoreAny(User $user): bool
    {
        return $this->canManageApprover($user, 'restore_any_rekrutmen_approver', 'restore_any_rekrutmen_request::man::power');
    }

    private function canManageApprover(User $user, string $approverPermission, string $fallbackPermission): bool
    {
        return $user->can($approverPermission) || $user->can($fallbackPermission);
    }
}
