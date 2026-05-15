<?php

namespace Cesa\Rekrutmen\Policies;

use Cesa\Rekrutmen\Models\RekrutmenPipeline;
use Illuminate\Auth\Access\HandlesAuthorization;
use Webkul\Security\Models\User;
use Webkul\Security\Traits\HasScopedPermissions;

class RekrutmenPipelinePolicy
{
    use HandlesAuthorization, HasScopedPermissions;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_rekrutmen_rekrutmen::pipeline');
    }

    public function view(User $user, RekrutmenPipeline $rekrutmenPipeline): bool
    {
        return $user->can('view_rekrutmen_rekrutmen::pipeline')
            && $this->hasAccess($user, $rekrutmenPipeline, 'creator');
    }

    public function create(User $user): bool
    {
        return $user->can('create_rekrutmen_rekrutmen::pipeline');
    }

    public function update(User $user, RekrutmenPipeline $rekrutmenPipeline): bool
    {
        return $user->can('update_rekrutmen_rekrutmen::pipeline')
            && $this->hasAccess($user, $rekrutmenPipeline, 'creator');
    }

    public function delete(User $user, RekrutmenPipeline $rekrutmenPipeline): bool
    {
        return $user->can('delete_rekrutmen_rekrutmen::pipeline')
            && $this->hasAccess($user, $rekrutmenPipeline, 'creator');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_rekrutmen_rekrutmen::pipeline');
    }

    public function forceDelete(User $user, RekrutmenPipeline $rekrutmenPipeline): bool
    {
        return $user->can('force_delete_rekrutmen_rekrutmen::pipeline')
            && $this->hasAccess($user, $rekrutmenPipeline, 'creator');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_rekrutmen_rekrutmen::pipeline');
    }

    public function restore(User $user, RekrutmenPipeline $rekrutmenPipeline): bool
    {
        return $user->can('restore_rekrutmen_rekrutmen::pipeline')
            && $this->hasAccess($user, $rekrutmenPipeline, 'creator');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_rekrutmen_rekrutmen::pipeline');
    }
}
