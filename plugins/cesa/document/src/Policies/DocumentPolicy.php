<?php

namespace Cesa\Document\Policies;

use Cesa\Document\Models\Document;
use Illuminate\Auth\Access\HandlesAuthorization;
use Webkul\Security\Models\User;
use Webkul\Security\Traits\HasScopedPermissions;

class DocumentPolicy
{
    use HandlesAuthorization, HasScopedPermissions;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_document_document');
    }

    public function view(User $user, Document $document): bool
    {
        return $user->can('view_document_document')
            && $this->hasAccess($user, $document, 'creator');
    }

    public function create(User $user): bool
    {
        return $user->can('create_document_document');
    }

    public function update(User $user, Document $document): bool
    {
        return $user->can('update_document_document')
            && $this->hasAccess($user, $document, 'creator');
    }

    public function delete(User $user, Document $document): bool
    {
        return $user->can('delete_document_document')
            && $this->hasAccess($user, $document, 'creator');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_document_document');
    }

    public function forceDelete(User $user, Document $document): bool
    {
        return $user->can('force_delete_document_document')
            && $this->hasAccess($user, $document, 'creator');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_document_document');
    }

    public function restore(User $user, Document $document): bool
    {
        return $user->can('restore_document_document')
            && $this->hasAccess($user, $document, 'creator');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_document_document');
    }

    public function reorder(User $user): bool
    {
        return $user->can('reorder_document_document');
    }
}
