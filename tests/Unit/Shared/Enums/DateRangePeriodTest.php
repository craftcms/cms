<?php

declare(strict_types=1);

use craft\helpers\DateTimeHelper;
use CraftCms\Cms\Shared\Enums\DateRangePeriod;

beforeEach(function () {
    DateTimeHelper::pause();
});

afterEach(function () {
    DateTimeHelper::resume();
});

test('interval returns expected offsets', function (DateInterval $expected, float|int $length, DateRangePeriod $periodType) {
    $now = DateTimeHelper::now();
    $dateInterval = $periodType->interval($length);

    expect((clone $now)->add($dateInterval)->getTimestamp())
        ->toBe((clone $now)->add($expected)->getTimestamp());
})->with([
    'daysFullFromNow' => [DateInterval::createFromDateString('4 days'), 4, DateRangePeriod::DaysFromNow],
    'daysDecimalFromNow' => [DateInterval::createFromDateString('4 days + 12 hours'), 4.5, DateRangePeriod::DaysFromNow],
    'hoursFullFromNow' => [DateInterval::createFromDateString('4 hours'), 4, DateRangePeriod::HoursFromNow],
    'hoursDecimalFromNow' => [DateInterval::createFromDateString('4 hours + 30 minutes'), 4.5, DateRangePeriod::HoursFromNow],
    'minutesFullFromNow' => [DateInterval::createFromDateString('4 minutes'), 4, DateRangePeriod::MinutesFromNow],
    'minutesDecimalFromNow' => [DateInterval::createFromDateString('4 minutes + 30 seconds'), 4.5, DateRangePeriod::MinutesFromNow],
    'weeksFullFromNow' => [DateInterval::createFromDateString('4 weeks'), 4, DateRangePeriod::WeeksFromNow],
    'weeksDecimalFromNow' => [DateInterval::createFromDateString('4 weeks + 4 days'), 4.5, DateRangePeriod::WeeksFromNow],
    'secondsFullFromNow' => [DateInterval::createFromDateString('4 seconds'), 4, DateRangePeriod::SecondsFromNow],
    'secondsDecimalFromNow' => [DateInterval::createFromDateString('5 seconds'), 4.5, DateRangePeriod::SecondsFromNow],
    'daysFullAgo' => [DateInterval::createFromDateString('-4 days'), 4, DateRangePeriod::DaysAgo],
    'daysDecimalAgo' => [DateInterval::createFromDateString('-4 days - 12 hours'), 4.5, DateRangePeriod::DaysAgo],
    'hoursFullAgo' => [DateInterval::createFromDateString('-4 hours'), 4, DateRangePeriod::HoursAgo],
    'hoursDecimalAgo' => [DateInterval::createFromDateString('-4 hours - 30 minutes'), 4.5, DateRangePeriod::HoursAgo],
    'minutesFullAgo' => [DateInterval::createFromDateString('-4 minutes'), 4, DateRangePeriod::MinutesAgo],
    'minutesDecimalAgo' => [DateInterval::createFromDateString('-4 minutes - 30 seconds'), 4.5, DateRangePeriod::MinutesAgo],
    'weeksFullAgo' => [DateInterval::createFromDateString('-4 weeks'), 4, DateRangePeriod::WeeksAgo],
    'weeksDecimalAgo' => [DateInterval::createFromDateString('-4 weeks - 4 days'), 4.5, DateRangePeriod::WeeksAgo],
    'secondsFullAgo' => [DateInterval::createFromDateString('-4 seconds'), 4, DateRangePeriod::SecondsAgo],
    'secondsDecimalAgo' => [DateInterval::createFromDateString('-5 seconds'), 4.5, DateRangePeriod::SecondsAgo],
    'daysFullNegFromNow' => [DateInterval::createFromDateString('-4 days'), -4, DateRangePeriod::DaysFromNow],
    'daysDecimalNegFromNow' => [DateInterval::createFromDateString('-4 days - 12 hours'), -4.5, DateRangePeriod::DaysFromNow],
    'hoursFullNegFromNow' => [DateInterval::createFromDateString('-4 hours'), -4, DateRangePeriod::HoursFromNow],
    'hoursDecimalNegFromNow' => [DateInterval::createFromDateString('-4 hours - 30 minutes'), -4.5, DateRangePeriod::HoursFromNow],
    'minutesFullNegFromNow' => [DateInterval::createFromDateString('-4 minutes'), -4, DateRangePeriod::MinutesFromNow],
    'minutesDecimalNegFromNow' => [DateInterval::createFromDateString('-4 minutes - 30 seconds'), -4.5, DateRangePeriod::MinutesFromNow],
    'weeksFullNegFromNow' => [DateInterval::createFromDateString('-4 weeks'), -4, DateRangePeriod::WeeksFromNow],
    'weeksDecimalNegFromNow' => [DateInterval::createFromDateString('-5 weeks + 4 days'), -4.5, DateRangePeriod::WeeksFromNow],
    'secondsFullNegFromNow' => [DateInterval::createFromDateString('-4 seconds'), -4, DateRangePeriod::SecondsFromNow],
    'secondsDecimalNegFromNow' => [DateInterval::createFromDateString('-5 seconds'), -4.5, DateRangePeriod::SecondsFromNow],
]);
