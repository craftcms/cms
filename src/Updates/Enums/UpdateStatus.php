<?php

declare(strict_types=1);

namespace CraftCms\Cms\Updates\Enums;

/**
 * @internal
 */
enum UpdateStatus: string
{
    case ELIGIBLE = 'eligible';
    case BREAKPOINT = 'breakpoint';
    case EXPIRED = 'expired';
}
