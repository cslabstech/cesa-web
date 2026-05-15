<?php

namespace Cesa\Lead\Policies;

use Cesa\Lead\Models\Lead;
use Illuminate\Auth\Access\HandlesAuthorization;
use Webkul\Security\Models\User;
use Webkul\Security\Traits\HasScopedPermissions;

class LeadPolicy
{
    use HandlesAuthorization, HasScopedPermissions;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_lead_lead');
    }

    public function view(User $user, Lead $lead): bool
    {
        if (! $user->can('view_lead_lead')) {
            return false;
        }

        return $this->hasAccess($user, $lead, 'creator');
    }

    public function create(User $user): bool
    {
        return $user->can('create_lead_lead');
    }

    public function update(User $user, Lead $lead): bool
    {
        if (! $user->can('update_lead_lead')) {
            return false;
        }

        return $this->hasAccess($user, $lead, 'creator');
    }

    public function delete(User $user, Lead $lead): bool
    {
        if (! $user->can('delete_lead_lead')) {
            return false;
        }

        return $this->hasAccess($user, $lead, 'creator');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_lead_lead');
    }

    public function forceDelete(User $user, Lead $lead): bool
    {
        if (! $user->can('force_delete_lead_lead')) {
            return false;
        }

        return $this->hasAccess($user, $lead, 'creator');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_lead_lead');
    }

    public function restore(User $user, Lead $lead): bool
    {
        if (! $user->can('restore_lead_lead')) {
            return false;
        }

        return $this->hasAccess($user, $lead, 'creator');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_lead_lead');
    }

    public function reorder(User $user): bool
    {
        return $user->can('reorder_lead_lead');
    }
}
