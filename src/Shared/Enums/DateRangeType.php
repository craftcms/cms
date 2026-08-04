<?php

declare(strict_types=1);

namespace CraftCms\Cms\Shared\Enums;

use CraftCms\Cms\Support\DateTimeHelper;
use DateTimeInterface;
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

    /** @return array{DateTimeInterface, DateTimeInterface} */
    public function range(): array
    {
        return match ($this) {
            DateRangeType::Today => [
                today(),
                today()->addDay(),
            ],
            DateRangeType::ThisWeek => [
                now()->startOfWeek(DateTimeHelper::firstWeekDay()),
                now()->startOfWeek(DateTimeHelper::firstWeekDay())->addWeek(),
            ],
            DateRangeType::ThisMonth => [
                today()->startOfMonth(),
                today()->startOfMonth()->addMonth(),
            ],
            DateRangeType::ThisYear => [
                today()->startOfYear(),
                today()->startOfYear()->addYear(),
            ],
            DateRangeType::Past7Days => [
                today()->subDays(7),
                now(),
            ],
            DateRangeType::Past30Days => [
                today()->subDays(30),
                now(),
            ],
            DateRangeType::Past90Days => [
                today()->subDays(90),
                now(),
            ],
            DateRangeType::PastYear => [
                today()->subYear(),
                now(),
            ],
            default => throw new Exception('Invalid range type: '.$this->value),
        };
    }
}
