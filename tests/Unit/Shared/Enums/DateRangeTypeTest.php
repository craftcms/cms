<?php

declare(strict_types=1);

use CraftCms\Cms\Shared\Enums\DateRangeType;
use CraftCms\Cms\Support\DateTimeHelper;
use Illuminate\Support\Facades\Date;

beforeEach(function () {
    Date::setTestNow(now());
});

afterEach(function () {
    Date::setTestNow();
});

test('range returns expected start and end dates', function (DateRangeType $rangeType, Closure $expectedStartDate, Closure $expectedEndDate) {
    [$startDate, $endDate] = $rangeType->range();

    expect($startDate->getTimestamp())->toBe($expectedStartDate()->getTimestamp())
        ->and($endDate->getTimestamp())->toBe($expectedEndDate()->getTimestamp());
})->with([
    'today' => [
        DateRangeType::Today,
        today(...),
        fn () => today()->addDay(),
    ],
    'thisWeek' => [
        DateRangeType::ThisWeek,
        fn () => now()->startOfWeek(DateTimeHelper::firstWeekDay()),
        fn () => now()->startOfWeek(DateTimeHelper::firstWeekDay())->addWeek(),
    ],
    'thisMonth' => [
        DateRangeType::ThisMonth,
        fn () => today()->startOfMonth(),
        fn () => today()->startOfMonth()->addMonth(),
    ],
    'thisYear' => [
        DateRangeType::ThisYear,
        fn () => today()->startOfYear(),
        fn () => today()->startOfYear()->addYear(),
    ],
    'past7Days' => [
        DateRangeType::Past7Days,
        fn () => today()->subDays(7),
        now(...),
    ],
    'past30Days' => [
        DateRangeType::Past30Days,
        fn () => today()->subDays(30),
        now(...),
    ],
    'past90Days' => [
        DateRangeType::Past90Days,
        fn () => today()->subDays(90),
        now(...),
    ],
    'pastYear' => [
        DateRangeType::PastYear,
        fn () => today()->subYear(),
        now(...),
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
