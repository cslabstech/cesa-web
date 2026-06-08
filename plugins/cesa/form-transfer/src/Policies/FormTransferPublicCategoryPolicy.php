<?php

namespace Cesa\FormTransfer\Policies;

use Cesa\FormTransfer\Models\FormTransferPublicCategory;
use Illuminate\Auth\Access\HandlesAuthorization;
use Webkul\Security\Models\User;
use Webkul\Security\Traits\HasScopedPermissions;

class FormTransferPublicCategoryPolicy
{
    use HandlesAuthorization, HasScopedPermissions;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_form_transfer_public::category');
    }

    public function view(User $user, FormTransferPublicCategory $category): bool
    {
        return $user->can('view_form_transfer_public::category')
            && $this->hasAccess($user, $category, 'creator');
    }

    public function create(User $user): bool
    {
        return $user->can('create_form_transfer_public::category');
    }

    public function update(User $user, FormTransferPublicCategory $category): bool
    {
        return $user->can('update_form_transfer_public::category')
            && $this->hasAccess($user, $category, 'creator');
    }

    public function delete(User $user, FormTransferPublicCategory $category): bool
    {
        if ($category->isBuiltIn()) {
            return false;
        }

        return $user->can('delete_form_transfer_public::category')
            && $this->hasAccess($user, $category, 'creator');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_form_transfer_public::category');
    }

    public function forceDelete(User $user, FormTransferPublicCategory $category): bool
    {
        if ($category->isBuiltIn()) {
            return false;
        }

        return $user->can('force_delete_form_transfer_public::category')
            && $this->hasAccess($user, $category, 'creator');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_form_transfer_public::category');
    }

    public function restore(User $user, FormTransferPublicCategory $category): bool
    {
        return $user->can('restore_form_transfer_public::category')
            && $this->hasAccess($user, $category, 'creator');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_form_transfer_public::category');
    }
}
