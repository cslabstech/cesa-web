<?php

namespace Cesa\Payroll\Policies;

use Cesa\Payroll\Models\PayrollRecord;
use Illuminate\Auth\Access\HandlesAuthorization;
use Webkul\Security\Models\User;
use Webkul\Security\Traits\HasScopedPermissions;

class PayrollRecordPolicy
{
    use HandlesAuthorization, HasScopedPermissions;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_payroll_payroll::record');
    }

    public function view(User $user, PayrollRecord $payrollRecord): bool
    {
        return $user->can('view_payroll_payroll::record')
            && $this->hasAccess($user, $payrollRecord, 'creator');
    }

    public function delete(User $user, PayrollRecord $payrollRecord): bool
    {
        return $user->can('delete_payroll_payroll::record')
            && $this->hasAccess($user, $payrollRecord, 'creator');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_payroll_payroll::record');
    }
}
