<?php

namespace Cesa\Helpdesk\Policies;

use Cesa\Helpdesk\Models\TicketStatus;
use Illuminate\Auth\Access\HandlesAuthorization;
use Webkul\Security\Models\User;
use Webkul\Security\Traits\HasScopedPermissions;

class TicketStatusPolicy
{
    use HandlesAuthorization, HasScopedPermissions;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_helpdesk_ticket::status');
    }

    public function view(User $user, TicketStatus $ticketStatus): bool
    {
        return $user->can('view_helpdesk_ticket::status')
            && $this->hasAccess($user, $ticketStatus, 'creator');
    }

    public function create(User $user): bool
    {
        return $user->can('create_helpdesk_ticket::status');
    }

    public function update(User $user, TicketStatus $ticketStatus): bool
    {
        return $user->can('update_helpdesk_ticket::status')
            && $this->hasAccess($user, $ticketStatus, 'creator');
    }

    public function delete(User $user, TicketStatus $ticketStatus): bool
    {
        return $user->can('delete_helpdesk_ticket::status')
            && $this->hasAccess($user, $ticketStatus, 'creator');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_helpdesk_ticket::status');
    }

    public function forceDelete(User $user, TicketStatus $ticketStatus): bool
    {
        return $user->can('force_delete_helpdesk_ticket::status')
            && $this->hasAccess($user, $ticketStatus, 'creator');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_helpdesk_ticket::status');
    }

    public function restore(User $user, TicketStatus $ticketStatus): bool
    {
        return $user->can('restore_helpdesk_ticket::status')
            && $this->hasAccess($user, $ticketStatus, 'creator');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_helpdesk_ticket::status');
    }
}
