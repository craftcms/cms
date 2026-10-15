<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\ViewModels;

use CraftCms\Cms\Cms;
use CraftCms\Cms\Cp\SelectOptions;
use CraftCms\Cms\Ui\Controls\Combobox;
use CraftCms\Cms\Ui\Controls\Date;
use CraftCms\Cms\Ui\Controls\DateTime;
use CraftCms\Cms\Ui\Controls\Time;
use CraftCms\Cms\Ui\Nodes\Field;
use CraftCms\Cms\Ui\Nodes\Group;
use DateTimeInterface;

use function CraftCms\Cms\t;

/**
 * A release date edited as separately labeled date, time, and time zone
 * inputs, submitted as `{date, time, timezone}` under one path — or, with
 * {@see dateTimeField()}, as a single date and time control in the system
 * time zone, like entries’ Post Date.
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

    /**
     * A release date edited with the same date and time control as entries’
     * Post Date, in the system time zone. Its value comes from {@see dateTimeValue()}.
     *
     * @param  bool  $fullWidth  Whether the control fills its column, as in a
     *                           details sidebar like entries’
     */
    public static function dateTimeField(string $path, string $label, bool $fullWidth = false): Field
    {
        return Field::make($label, DateTime::make($path)
            ->showTime()
            ->fullWidth($fullWidth)
            // Stored times aren't constrained to a picker step, so a coarser
            // increment would make any off-step value fail validation.
            ->minuteIncrement(1));
    }

    /**
     * The value for {@see dateTimeField()}: the date in the system time zone,
     * which the control submits back with it since it doesn't show the zone.
     *
     * @return array{date: string, time: string, timezone: string}
     */
    public static function dateTimeValue(?DateTimeInterface $date): array
    {
        return self::value($date === null ? null : \Illuminate\Support\Facades\Date::instance($date)->setTimezone(Cms::timezone()));
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
