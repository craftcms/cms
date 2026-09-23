<?php

declare(strict_types=1);

namespace CraftCms\Cms\Workflow\UserReview;

enum UserReviewApprovalMode: string
{
    case Total = 'total';
    case PerGroup = 'per-group';
}
