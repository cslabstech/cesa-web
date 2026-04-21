<?php

namespace Cesa\FormTransfer\Tests\Unit\Filament;

use Cesa\FormTransfer\Enums\ApprovalStatus;
use Cesa\FormTransfer\Filament\Resources\TransferRequestResource;
use Cesa\FormTransfer\Tests\FormTransferTestCase;

class TransferRequestApprovalLinkTest extends FormTransferTestCase
{
    public function test_it_builds_public_approval_url_using_application_base_url(): void
    {
        config()->set('app.url', 'https://web-cesa.test');

        $url = TransferRequestResource::getPublicApprovalUrlFor('task-123');

        $this->assertSame('https://web-cesa.test/transfer-requests/approval/task-123', $url);
    }

    public function test_it_builds_public_progress_url_using_application_base_url(): void
    {
        config()->set('app.url', 'https://web-cesa.test');

        $url = TransferRequestResource::getPublicProgressUrlFor('05997372-2828-42d3-9561-b1b43b86dbdd');

        $this->assertSame(
            'https://web-cesa.test/transfer-requests/progress/05997372-2828-42d3-9561-b1b43b86dbdd',
            $url,
        );
    }

    public function test_it_only_builds_public_approval_url_for_pending_steps(): void
    {
        config()->set('app.url', 'https://web-cesa.test');

        $pendingUrl = TransferRequestResource::getPendingPublicApprovalUrlFor('task-123', ApprovalStatus::PENDING->value);
        $waitingUrl = TransferRequestResource::getPendingPublicApprovalUrlFor('task-123', ApprovalStatus::WAITING->value);
        $approvedUrl = TransferRequestResource::getPendingPublicApprovalUrlFor('task-123', ApprovalStatus::APPROVED->value);

        $this->assertSame('https://web-cesa.test/transfer-requests/approval/task-123', $pendingUrl);
        $this->assertNull($waitingUrl);
        $this->assertNull($approvedUrl);
    }

    public function test_it_returns_null_when_approval_task_id_is_missing(): void
    {
        $this->assertNull(TransferRequestResource::getPublicApprovalUrlFor(null));
        $this->assertNull(TransferRequestResource::getPublicApprovalUrlFor(''));
        $this->assertNull(TransferRequestResource::getPendingPublicApprovalUrlFor(null, ApprovalStatus::PENDING->value));
        $this->assertNull(TransferRequestResource::getPublicProgressUrlFor(null));
        $this->assertNull(TransferRequestResource::getPublicProgressUrlFor(''));
    }
}
