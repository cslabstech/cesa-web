<?php

namespace Cesa\Helpdesk\Policies;

use Cesa\Helpdesk\Models\ProblemCategory;
use Illuminate\Auth\Access\HandlesAuthorization;
use Webkul\Security\Models\User;
use Webkul\Security\Traits\HasScopedPermissions;

class ProblemCategoryPolicy
{
    use HandlesAuthorization, HasScopedPermissions;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_helpdesk_problem::category');
    }

    public function view(User $user, ProblemCategory $problemCategory): bool
    {
        return $user->can('view_helpdesk_problem::category')
            && $this->hasAccess($user, $problemCategory, 'creator');
    }

    public function create(User $user): bool
    {
        return $user->can('create_helpdesk_problem::category');
    }

    public function update(User $user, ProblemCategory $problemCategory): bool
    {
        return $user->can('update_helpdesk_problem::category')
            && $this->hasAccess($user, $problemCategory, 'creator');
    }

    public function delete(User $user, ProblemCategory $problemCategory): bool
    {
        return $user->can('delete_helpdesk_problem::category')
            && $this->hasAccess($user, $problemCategory, 'creator');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_helpdesk_problem::category');
    }

    public function forceDelete(User $user, ProblemCategory $problemCategory): bool
    {
        return $user->can('force_delete_helpdesk_problem::category')
            && $this->hasAccess($user, $problemCategory, 'creator');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_helpdesk_problem::category');
    }

    public function restore(User $user, ProblemCategory $problemCategory): bool
    {
        return $user->can('restore_helpdesk_problem::category')
            && $this->hasAccess($user, $problemCategory, 'creator');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_helpdesk_problem::category');
    }
}
