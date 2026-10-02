<?php

declare(strict_types=1);

namespace CraftCms\Cms\Workflow\Enums;

enum WorkflowStageStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Failed = 'failed';
}
