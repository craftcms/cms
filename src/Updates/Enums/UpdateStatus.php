<?php

namespace CraftCms\Cms\Updates\Enums;

/**
 * @internal
 *
 * @since 6.0.0
 */
enum UpdateStatus: string
{
    case ELIGIBLE = 'eligible';
    case BREAKPOINT = 'breakpoint';
    case EXPIRED = 'expired';
}
