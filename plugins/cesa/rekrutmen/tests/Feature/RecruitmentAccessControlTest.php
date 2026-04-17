<?php

namespace Cesa\Rekrutmen\Tests\Feature;

use Cesa\Rekrutmen\Filament\Pages\RecruitmentProgressReportPage;
use Cesa\Rekrutmen\Filament\Resources\ActivityLogResource;
use Cesa\Rekrutmen\Tests\RekrutmenTestCase;
use Filament\Facades\Filament;
use Spatie\Permission\Models\Permission;
use Webkul\Security\Models\User;

class RecruitmentAccessControlTest extends RekrutmenTestCase
{
    public function test_activity_log_and_report_page_require_history_permissions(): void
    {
        Filament::setCurrentPanel('admin');

        $userWithoutPermission = User::factory()->create([
            'is_active' => true,
        ]);

        Permission::findOrCreate('view_any_cesa::rekrutmen::models::job::application::history', 'web');
        Permission::findOrCreate('create_cesa::rekrutmen::models::job::application::history', 'web');

        $this->actingAs($userWithoutPermission);

        $this->assertFalse(ActivityLogResource::canAccess());
        $this->assertFalse(ActivityLogResource::canCreate());
        $this->assertFalse(RecruitmentProgressReportPage::canAccess());
        $this->getJson('/api/recruitment/progress-report')->assertForbidden();
        $this->getJson('/api/recruitment/progress-report/timeline')->assertForbidden();
        $this->getJson('/api/recruitment/progress-report/overview')->assertForbidden();

        $userWithoutPermission->givePermissionTo([
            'view_any_cesa::rekrutmen::models::job::application::history',
            'create_cesa::rekrutmen::models::job::application::history',
        ]);

        $this->assertTrue(ActivityLogResource::canAccess());
        $this->assertTrue(ActivityLogResource::canCreate());
        $this->assertTrue(RecruitmentProgressReportPage::canAccess());
    }

    public function test_activity_log_and_report_page_accept_resource_style_activity_permissions(): void
    {
        Filament::setCurrentPanel('admin');

        $userWithResourcePermissions = User::factory()->create([
            'is_active' => true,
        ]);

        Permission::findOrCreate('view_any_rekrutmen_activity::log', 'web');
        Permission::findOrCreate('create_rekrutmen_activity::log', 'web');

        $this->actingAs($userWithResourcePermissions);

        $this->assertFalse(ActivityLogResource::canAccess());
        $this->assertFalse(ActivityLogResource::canCreate());
        $this->assertFalse(RecruitmentProgressReportPage::canAccess());

        $userWithResourcePermissions->givePermissionTo([
            'view_any_rekrutmen_activity::log',
            'create_rekrutmen_activity::log',
        ]);

        $this->assertTrue(ActivityLogResource::canAccess());
        $this->assertTrue(ActivityLogResource::canCreate());
        $this->assertTrue(RecruitmentProgressReportPage::canAccess());
    }
}
