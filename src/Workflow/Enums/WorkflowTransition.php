<?php

declare(strict_types=1);

namespace CraftCms\Cms\Workflow\Enums;

enum WorkflowTransition: string
{
    case Submit = 'submit';
    case Override = 'override';
    case Approve = 'approve';
    case Reject = 'reject';
    case RequestReview = 'request-review';
    case Restart = 'reset';
    case StageApproved = 'stage-approved';
    case StageFailed = 'stage-failed';
    case Invalidate = 'invalidate';
    case Publish = 'publish';
}
