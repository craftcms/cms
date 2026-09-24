<?php

declare(strict_types=1);

namespace CraftCms\Cms\Workflow\UserReview;

enum UserReviewDecision: string
{
    case Approved = 'approved';
    case Rejected = 'rejected';
}
