<?php

use Cesa\Document\Filament\Resources\DocumentResource;
use Cesa\Document\Models\Document;
use Cesa\ExitClearance\Models\Approver as ExitClearanceApprover;
use Cesa\ExitClearance\Models\Department as ExitClearanceDepartment;
use Cesa\ExitClearance\Models\Request as ExitClearanceRequest;
use Cesa\FormTransfer\Filament\Resources\TransferRequestResource;
use Cesa\FormTransfer\Models\TransferRequest;
use Cesa\Helpdesk\Filament\Resources\TicketResource;
use Cesa\Helpdesk\Models\Ticket;
use Cesa\Lead\Filament\Resources\LeadResource;
use Cesa\Lead\Models\Lead;
use Cesa\Payroll\Filament\Resources\PayrollRecordResource;
use Cesa\Payroll\Models\PayrollRecord;
use Cesa\Presensi\Filament\Resources\AttendanceResource;
use Cesa\Presensi\Models\Attendance;
use Cesa\Rekrutmen\Filament\Resources\RequestManPowerResource;
use Cesa\Rekrutmen\Models\Approver as RekrutmenApprover;
use Cesa\Rekrutmen\Models\Division as RekrutmenDivision;
use Cesa\Rekrutmen\Models\RequestManPower;
use Webkul\Security\Traits\HasNullableCreator;
use Webkul\Security\Traits\HasResourcePermissionQuery;

test('cesa models use nullable creator ownership metadata', function (): void {
    foreach ([
        Lead::class,
        Document::class,
        ExitClearanceApprover::class,
        ExitClearanceDepartment::class,
        ExitClearanceRequest::class,
        TransferRequest::class,
        Ticket::class,
        PayrollRecord::class,
        Attendance::class,
        RekrutmenApprover::class,
        RekrutmenDivision::class,
        RequestManPower::class,
    ] as $model) {
        expect(class_uses_recursive($model))->toContain(HasNullableCreator::class);
    }
});

test('cesa resources apply resource permission query scope', function (): void {
    foreach ([
        LeadResource::class,
        DocumentResource::class,
        TransferRequestResource::class,
        TicketResource::class,
        PayrollRecordResource::class,
        AttendanceResource::class,
        RequestManPowerResource::class,
    ] as $resource) {
        expect(class_uses_recursive($resource))->toContain(HasResourcePermissionQuery::class);
    }
});
