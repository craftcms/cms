<?php

declare(strict_types=1);

use CraftCms\Cms\Shared\Enums\DateRangeType;
use CraftCms\Cms\Support\DateTimeHelper;

beforeEach(function () {
    DateTimeHelper::pause();
});

afterEach(function () {
    DateTimeHelper::resume();
});

test('range returns expected start and end dates', function (DateRangeType $rangeType, Closure $expectedStartDate, Closure $expectedEndDate) {
    [$startDate, $endDate] = $rangeType->range();

    expect($startDate->getTimestamp())->toBe($expectedStartDate()->getTimestamp())
        ->and($endDate->getTimestamp())->toBe($expectedEndDate()->getTimestamp());
})->with([
    'today' => [
        DateRangeType::Today,
        DateTimeHelper::today(...),
        DateTimeHelper::tomorrow(...),
    ],
    'thisWeek' => [
        DateRangeType::ThisWeek,
        DateTimeHelper::thisWeek(...),
        DateTimeHelper::nextWeek(...),
    ],
    'thisMonth' => [
        DateRangeType::ThisMonth,
        DateTimeHelper::thisMonth(...),
        DateTimeHelper::nextMonth(...),
    ],
    'thisYear' => [
        DateRangeType::ThisYear,
        DateTimeHelper::thisYear(...),
        DateTimeHelper::nextYear(...),
    ],
    'past7Days' => [
        DateRangeType::Past7Days,
        fn () => DateTimeHelper::today()->modify('-7 days'),
        DateTimeHelper::now(...),
    ],
    'past30Days' => [
        DateRangeType::Past30Days,
        fn () => DateTimeHelper::today()->modify('-30 days'),
        DateTimeHelper::now(...),
    ],
    'past90Days' => [
        DateRangeType::Past90Days,
        fn () => DateTimeHelper::today()->modify('-90 days'),
        DateTimeHelper::now(...),
    ],
    'pastYear' => [
        DateRangeType::PastYear,
        fn () => DateTimeHelper::today()->modify('-1 year'),
        DateTimeHelper::now(...),
    ],
]);

test('range throws for non-range-based types', function (DateRangeType $rangeType) {
    $this->expectException(Exception::class);
    $this->expectExceptionMessage("Invalid range type: {$rangeType->value}");

    $rangeType->range();
})->with([
    DateRangeType::Before,
    DateRangeType::After,
    DateRangeType::Range,
]);
