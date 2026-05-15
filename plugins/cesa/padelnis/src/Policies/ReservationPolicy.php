<?php

namespace Cesa\Padelnis\Policies;

use Cesa\Padelnis\Models\Reservation;
use Illuminate\Auth\Access\HandlesAuthorization;
use Webkul\Security\Models\User;
use Webkul\Security\Traits\HasScopedPermissions;

class ReservationPolicy
{
    use HandlesAuthorization, HasScopedPermissions;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_padelnis_reservation');
    }

    public function view(User $user, Reservation $reservation): bool
    {
        return $user->can('view_padelnis_reservation')
            && $this->hasAccess($user, $reservation, 'creator');
    }

    public function create(User $user): bool
    {
        return $user->can('create_padelnis_reservation');
    }

    public function update(User $user, Reservation $reservation): bool
    {
        return $user->can('update_padelnis_reservation')
            && $this->hasAccess($user, $reservation, 'creator');
    }

    public function delete(User $user, Reservation $reservation): bool
    {
        return $user->can('delete_padelnis_reservation')
            && $this->hasAccess($user, $reservation, 'creator');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_padelnis_reservation');
    }

    public function forceDelete(User $user, Reservation $reservation): bool
    {
        return $user->can('force_delete_padelnis_reservation')
            && $this->hasAccess($user, $reservation, 'creator');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_padelnis_reservation');
    }

    public function restore(User $user, Reservation $reservation): bool
    {
        return $user->can('restore_padelnis_reservation')
            && $this->hasAccess($user, $reservation, 'creator');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_padelnis_reservation');
    }
}
