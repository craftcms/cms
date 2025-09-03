<?php

namespace CraftCms\Cms\Updates\Enums;

enum UpdateStatus: string
{
    case ELIGIBLE = 'eligible';
    case BREAKPOINT = 'breakpoint';
    case EXPIRED = 'expired';
}
