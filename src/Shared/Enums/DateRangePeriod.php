<?php

declare(strict_types=1);

namespace CraftCms\Cms\Shared\Enums;

use DateInterval;
use RuntimeException;

use function CraftCms\Cms\t;

enum DateRangePeriod: string
{
    case SecondsAgo = 'secondsAgo';
    case MinutesAgo = 'minutesAgo';
    case HoursAgo = 'hoursAgo';
    case DaysAgo = 'daysAgo';
    case WeeksAgo = 'weeksAgo';
    case SecondsFromNow = 'secondsFromNow';
    case MinutesFromNow = 'minutesFromNow';
    case HoursFromNow = 'hoursFromNow';
    case DaysFromNow = 'daysFromNow';
    case WeeksFromNow = 'weeksFromNow';

    public function label(): string
    {
        return match ($this) {
            self::MinutesAgo => t('minutes ago'),
            self::HoursAgo => t('hours ago'),
            self::DaysAgo => t('days ago'),
            self::MinutesFromNow => t('minutes from now'),
            self::HoursFromNow => t('hours from now'),
            self::DaysFromNow => t('days from now'),
            self::SecondsAgo => t('seconds ago'),
            self::WeeksAgo => t('weeks ago'),
            self::SecondsFromNow => t('seconds from now'),
            self::WeeksFromNow => t('weeks from now'),
        };
    }

    public function interval(float|int $length): DateInterval
    {
        // Cannot support months or years as they are variable in length
        if (! in_array($this, [
            self::SecondsAgo,
            self::MinutesAgo,
            self::HoursAgo,
            self::DaysAgo,
            self::WeeksAgo,
            self::SecondsFromNow,
            self::MinutesFromNow,
            self::HoursFromNow,
            self::DaysFromNow,
            self::WeeksFromNow,
        ], true)) {
            throw new RuntimeException('Invalid period type: '.$this->value);
        }

        if (in_array($this, [self::SecondsAgo, self::SecondsFromNow])) {
            $length = $intLength = round($length);
        } else {
            $intLength = floor($length);
        }

        $pos = in_array($this, [
            self::WeeksFromNow,
            self::DaysFromNow,
            self::HoursFromNow,
            self::MinutesFromNow,
            self::SecondsFromNow,
        ]);

        $str = sprintf('%s%s %s', $pos ? '' : '-', $intLength, match ($this) {
            self::WeeksAgo, self::WeeksFromNow => 'weeks',
            self::DaysAgo, self::DaysFromNow => 'days',
            self::HoursAgo, self::HoursFromNow => 'hours',
            self::MinutesAgo, self::MinutesFromNow => 'minutes',
            self::SecondsAgo, self::SecondsFromNow => 'seconds',
        });

        $rem = $length - $intLength;

        if ($rem) {
            $str .= sprintf(' %s %s', $pos ? '+' : '-', match ($this) {
                self::WeeksAgo, self::WeeksFromNow => sprintf('%s days', round($rem * 7)),
                self::DaysAgo, self::DaysFromNow => sprintf('%s hours', round($rem * 24)),
                self::HoursAgo, self::HoursFromNow => sprintf('%s minutes', round($rem * 60)),
                self::MinutesAgo, self::MinutesFromNow => sprintf('%s seconds', round($rem * 60)),
                self::SecondsAgo, self::SecondsFromNow => sprintf('%s seconds', round($rem)),
            });
        }

        return DateInterval::createFromDateString($str);
    }
}
