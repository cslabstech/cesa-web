<?php

namespace Cesa\Rekrutmen\Policies;

use Cesa\Rekrutmen\Models\JobApplicationHistory;
use Illuminate\Auth\Access\HandlesAuthorization;
use Webkul\Security\Models\User;

class JobApplicationHistoryPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $this->hasAnyPermission($user, [
            'view_any_cesa::rekrutmen::models::job::application::history',
            'view_any_rekrutmen_activity::log',
        ]);
    }

    public function view(User $user, JobApplicationHistory $jobApplicationHistory): bool
    {
        return $this->hasAnyPermission($user, [
            'view_cesa::rekrutmen::models::job::application::history',
        ]);
    }

    public function create(User $user): bool
    {
        return $this->hasAnyPermission($user, [
            'create_cesa::rekrutmen::models::job::application::history',
            'create_rekrutmen_activity::log',
        ]);
    }

    public function update(User $user, JobApplicationHistory $jobApplicationHistory): bool
    {
        return $this->hasAnyPermission($user, [
            'update_cesa::rekrutmen::models::job::application::history',
            'update_rekrutmen_job::application',
        ]);
    }

    public function delete(User $user, JobApplicationHistory $jobApplicationHistory): bool
    {
        return $this->hasAnyPermission($user, [
            'delete_cesa::rekrutmen::models::job::application::history',
            'delete_rekrutmen_activity::log',
        ]);
    }

    public function deleteAny(User $user): bool
    {
        return $this->hasAnyPermission($user, [
            'delete_any_cesa::rekrutmen::models::job::application::history',
        ]);
    }

    public function restore(User $user, JobApplicationHistory $jobApplicationHistory): bool
    {
        return $this->hasAnyPermission($user, [
            'restore_cesa::rekrutmen::models::job::application::history',
        ]);
    }

    public function restoreAny(User $user): bool
    {
        return $this->hasAnyPermission($user, [
            'restore_any_cesa::rekrutmen::models::job::application::history',
        ]);
    }

    public function forceDelete(User $user, JobApplicationHistory $jobApplicationHistory): bool
    {
        return $this->hasAnyPermission($user, [
            'force_delete_cesa::rekrutmen::models::job::application::history',
        ]);
    }

    public function forceDeleteAny(User $user): bool
    {
        return $this->hasAnyPermission($user, [
            'force_delete_any_cesa::rekrutmen::models::job::application::history',
        ]);
    }

    /**
     * @param  array<int, string>  $permissions
     */
    protected function hasAnyPermission(User $user, array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($user->can($permission)) {
                return true;
            }
        }

        return false;
    }
}
