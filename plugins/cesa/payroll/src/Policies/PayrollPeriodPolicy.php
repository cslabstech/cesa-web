<?php

namespace Cesa\Payroll\Policies;

use Cesa\Payroll\Models\PayrollPeriod;
use Illuminate\Auth\Access\HandlesAuthorization;
use Webkul\Security\Models\User;
use Webkul\Security\Traits\HasScopedPermissions;

class PayrollPeriodPolicy
{
    use HandlesAuthorization, HasScopedPermissions;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_payroll_payroll::period');
    }

    public function view(User $user, PayrollPeriod $payrollPeriod): bool
    {
        return $user->can('view_payroll_payroll::period')
            && $this->hasAccess($user, $payrollPeriod, 'creator');
    }

    public function create(User $user): bool
    {
        return $user->can('create_payroll_payroll::period');
    }

    public function update(User $user, PayrollPeriod $payrollPeriod): bool
    {
        return $user->can('update_payroll_payroll::period')
            && $this->hasAccess($user, $payrollPeriod, 'creator');
    }

    public function delete(User $user, PayrollPeriod $payrollPeriod): bool
    {
        return $user->can('delete_payroll_payroll::period')
            && $this->hasAccess($user, $payrollPeriod, 'creator');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_payroll_payroll::period');
    }
}
