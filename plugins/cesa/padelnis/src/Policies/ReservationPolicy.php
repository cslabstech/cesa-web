<?php

namespace Cesa\Padelnis\Policies;

use Cesa\Padelnis\Models\Reservation;
use Illuminate\Auth\Access\HandlesAuthorization;
use Webkul\Security\Models\User;

class ReservationPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_padelnis_reservation');
    }

    public function view(User $user, Reservation $reservation): bool
    {
        return $user->can('view_padelnis_reservation');
    }

    public function create(User $user): bool
    {
        return $user->can('create_padelnis_reservation');
    }

    public function update(User $user, Reservation $reservation): bool
    {
        return $user->can('update_padelnis_reservation');
    }

    public function delete(User $user, Reservation $reservation): bool
    {
        return $user->can('delete_padelnis_reservation');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_padelnis_reservation');
    }

    public function forceDelete(User $user, Reservation $reservation): bool
    {
        return $user->can('force_delete_padelnis_reservation');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_padelnis_reservation');
    }

    public function restore(User $user, Reservation $reservation): bool
    {
        return $user->can('restore_padelnis_reservation');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_padelnis_reservation');
    }
}
