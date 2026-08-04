<?php

declare(strict_types=1);

namespace CraftCms\Cms\Shared\Enums;

use function CraftCms\Cms\t;

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

    public function label(): string
    {
        return match ($this) {
            TimePeriod::Seconds => t('Seconds'),
            TimePeriod::Minutes => t('Minutes'),
            TimePeriod::Hours => t('Hours'),
            TimePeriod::Days => t('Days'),
            TimePeriod::Weeks => t('Weeks'),
            TimePeriod::Months => t('Months'),
            TimePeriod::Years => t('Years'),
        };
    }
}
