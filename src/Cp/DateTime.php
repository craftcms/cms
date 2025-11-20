<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp;

use DateTimeZone;

class DateTime
{
    public static function getTimeZoneOptions(?DateTime $offsetDate = null): array
    {
        // Assemble the timezone options array (Technique adapted from http://stackoverflow.com/a/7022536/1688568)
        $options = [];

        $offsetDate ??= new \DateTime;
        $offsetDate->setTimezone(new DateTimeZone('UTC'));
        $offsets = [];
        $timezoneIds = [];

        foreach (DateTimeZone::listIdentifiers() as $timezoneId) {
            $timezone = new DateTimeZone($timezoneId);
            $transition = $timezone->getTransitions($offsetDate->getTimestamp(), $offsetDate->getTimestamp());
            $abbr = $transition[0]['abbr'];

            $offset = round($timezone->getOffset($offsetDate) / 60);

            if ($offset) {
                $hour = floor($offset / 60);
                $minutes = floor(abs($offset) % 60);
                $format = sprintf('%+03d:%02u', $hour, $minutes);
            } else {
                $format = '';
            }

            $label = "(GMT$format)";
            if (preg_match('/^[A-Z]+$/', $abbr)) {
                $label .= " $abbr";
            }

            $data = [];

            if ($timezoneId !== 'UTC') {
                [, $city] = explode('/', $timezoneId, 2);
                // Cleanup, e.g. North_Dakota/New_Salem => New Salem, North Dakota
                $data['hint'] = str_replace('_', ' ', implode(', ', array_reverse(explode('/', $city))));
            }

            $offsets[] = $offset;
            $timezoneIds[] = $timezoneId;
            $options[] = array_filter([
                'value' => $timezoneId,
                'label' => $label,
                'data' => ! empty($data) ? $data : null,
            ]);
        }

        array_multisort($offsets, SORT_ASC, SORT_NUMERIC, $timezoneIds, $options);

        return $options;
    }
}
