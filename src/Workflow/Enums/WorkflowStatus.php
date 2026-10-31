<?php

declare(strict_types=1);

namespace CraftCms\Cms\Workflow\Enums;

use function CraftCms\Cms\t;

enum WorkflowStatus: string
{
    case NotSubmitted = 'notSubmitted';
    case Pending = 'pending';
    case Approved = 'approved';
    case Failed = 'failed';
    case Invalidated = 'invalidated';
    case Withdrawn = 'withdrawn';
    case Published = 'published';

    public function label(): string
    {
        return match ($this) {
            self::NotSubmitted => t('Not submitted'),
            self::Pending => t('Awaiting approval'),
            self::Approved => t('Approved'),
            self::Failed => t('Changes requested'),
            self::Invalidated => t('Approval reset'),
            self::Withdrawn => t('Withdrawn'),
            self::Published => t('Published'),
        };
    }

    public function indicator(): string
    {
        return match ($this) {
            self::NotSubmitted, self::Withdrawn => 'disabled',
            self::Pending => 'pending',
            self::Failed,
            self::Invalidated => 'expired',
            self::Approved => 'enabled',
            self::Published => 'live',
        };
    }
}
