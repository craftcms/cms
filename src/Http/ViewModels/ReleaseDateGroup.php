<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\ViewModels;

use CraftCms\Cms\Cms;
use CraftCms\Cms\Ui\Controls\DateTime;
use CraftCms\Cms\Ui\Nodes\Field;
use DateTimeInterface;
use Illuminate\Support\Facades\Date;

/**
 * A release date edited with a single date and time control in the user’s
 * time zone, like entries’ Post Date.
 *
 * @since 6.0.0
 */
class ReleaseDateGroup
{
    /**
     * A release date edited with the same date and time control as entries’
     * Post Date, in the user’s time zone. Its value comes from {@see dateTimeValue()}.
     *
     * @param  bool  $fullWidth  Whether the control fills its column, as in a
     *                           details sidebar like entries’
     * @param  string|null  $min  The earliest selectable date, as `Y-m-d`
     */
    public static function dateTimeField(string $path, string $label, bool $fullWidth = false, ?string $min = null): Field
    {
        return Field::make($label, DateTime::make($path)
            ->showTime()
            ->min($min)
            ->fullWidth($fullWidth)
            // Stored times aren't constrained to a picker step, so a coarser
            // increment would make any off-step value fail validation.
            ->minuteIncrement(1));
    }

    /**
     * The value for {@see dateTimeField()}: the date in the user’s time zone,
     * which is also the zone its submitted date and time are read in.
     *
     * @return array{date: string, time: string, timezone: string}
     */
    public static function dateTimeValue(?DateTimeInterface $date): array
    {
        return self::value($date === null ? null : Date::instance($date)->setTimezone(Cms::timezone()));
    }

    /** @return array{date: string, time: string, timezone: string} */
    private static function value(?DateTimeInterface $date): array
    {
        return [
            'date' => $date?->format('Y-m-d') ?? '',
            'time' => $date?->format('H:i') ?? '',
            'timezone' => Cms::timezone(),
        ];
    }
}
