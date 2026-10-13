<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\ViewModels;

use CraftCms\Cms\Cms;
use CraftCms\Cms\Cp\SelectOptions;
use CraftCms\Cms\Ui\Controls\Combobox;
use CraftCms\Cms\Ui\Controls\Date;
use CraftCms\Cms\Ui\Controls\Time;
use CraftCms\Cms\Ui\Nodes\Field;
use CraftCms\Cms\Ui\Nodes\Group;
use DateTimeInterface;

use function CraftCms\Cms\t;

/**
 * A release date edited as separately labeled date, time, and time zone
 * inputs, submitted as `{date, time, timezone}` under one path.
 *
 * @since 6.0.0
 */
class ReleaseDateGroup
{
    public static function make(string $path, string $label, ?string $min = null): Group
    {
        return Group::make($path, [
            Field::make(t('Date'), Date::make("$path.date")->min($min)),
            Field::make(t('Time'), Time::make("$path.time")),
            Field::make(t('Time zone'), Combobox::make("$path.timezone")
                ->options(SelectOptions::getTimeZoneOptions())
                ->showAllOnEmpty()),
        ])
            ->label($label)
            ->asField();
    }

    /** @return array{date: string, time: string, timezone: string} */
    public static function value(?DateTimeInterface $date, ?string $timezone = null): array
    {
        return [
            'date' => $date?->format('Y-m-d') ?? '',
            'time' => $date?->format('H:i') ?? '',
            'timezone' => $timezone ?? $date?->getTimezone()->getName() ?? Cms::timezone(),
        ];
    }
}
