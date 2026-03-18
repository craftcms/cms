<?php

declare(strict_types=1);

namespace CraftCms\Cms\Shared\Enums;

use craft\helpers\DateTimeHelper;
use DateTime;
use Exception;

use function CraftCms\Cms\t;

enum DateRangeType: string
{
    case Today = 'today';
    case ThisWeek = 'thisWeek';
    case ThisMonth = 'thisMonth';
    case ThisYear = 'thisYear';
    case Past7Days = 'past7Days';
    case Past30Days = 'past30Days';
    case Past90Days = 'past90Days';
    case PastYear = 'pastYear';
    case Before = 'before';
    case After = 'after';
    case Range = 'range';

    public function label(): string
    {
        return match ($this) {
            self::Today => t('Today'),
            self::ThisWeek => t('This week'),
            self::ThisMonth => t('This month'),
            self::ThisYear => t('This year'),
            self::Past7Days => t('Past {num} days', ['num' => 7]),
            self::Past30Days => t('Past {num} days', ['num' => 30]),
            self::Past90Days => t('Past {num} days', ['num' => 90]),
            self::PastYear => t('Past year'),
            self::Before => t('Before…'),
            self::After => t('After…'),
            self::Range => t('Range…'),
        };
    }

    /** @return array{DateTime, DateTime} */
    public function range(): array
    {
        return match ($this) {
            DateRangeType::Today => [
                DateTimeHelper::today(),
                DateTimeHelper::tomorrow(),
            ],
            DateRangeType::ThisWeek => [
                DateTimeHelper::thisWeek(),
                DateTimeHelper::nextWeek(),
            ],
            DateRangeType::ThisMonth => [
                DateTimeHelper::thisMonth(),
                DateTimeHelper::nextMonth(),
            ],
            DateRangeType::ThisYear => [
                DateTimeHelper::thisYear(),
                DateTimeHelper::nextYear(),
            ],
            DateRangeType::Past7Days => [
                DateTimeHelper::today()->modify('-7 days'),
                DateTimeHelper::now(),
            ],
            DateRangeType::Past30Days => [
                DateTimeHelper::today()->modify('-30 days'),
                DateTimeHelper::now(),
            ],
            DateRangeType::Past90Days => [
                DateTimeHelper::today()->modify('-90 days'),
                DateTimeHelper::now(),
            ],
            DateRangeType::PastYear => [
                DateTimeHelper::today()->modify('-1 year'),
                DateTimeHelper::now(),
            ],
            default => throw new Exception('Invalid range type: '.$this->value),
        };
    }
}
