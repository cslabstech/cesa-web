<?php

namespace Cesa\ExitClearance\Enums;

enum ApprovalStatus: string
{
    case PENDING = 'pending';
    case WAITING = 'waiting';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';

    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING  => __('exit-clearance::enums/approval-status.pending'),
            self::WAITING  => __('exit-clearance::enums/approval-status.waiting'),
            self::APPROVED => __('exit-clearance::enums/approval-status.approved'),
            self::REJECTED => __('exit-clearance::enums/approval-status.rejected'),
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::PENDING  => 'gray',
            self::WAITING  => 'gray',
            self::APPROVED => 'success',
            self::REJECTED => 'danger',
        };
    }
}
