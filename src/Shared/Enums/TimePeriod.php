<?php

declare(strict_types=1);

namespace CraftCms\Cms\Shared\Enums;

/**
 * PeriodType defines time period units.
 */
enum TimePeriod: string
{
    case Seconds = 'seconds';
    case Minutes = 'minutes';
    case Hours = 'hours';
    case Days = 'days';
    case Weeks = 'weeks';
    case Months = 'months';
    case Years = 'years';
}
