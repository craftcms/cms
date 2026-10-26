<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Http\ViewModels\ReleaseDateGroup;
use Illuminate\Support\Facades\Date;

it('gives the date and time control its value in the system time zone', function () {
    Cms::config()->timezone = 'Asia/Tokyo';

    expect(ReleaseDateGroup::dateTimeValue(Date::parse('2026-10-15 21:30:00', 'UTC')))->toBe([
        'date' => '2026-10-16',
        'time' => '06:30',
        'timezone' => 'Asia/Tokyo',
    ]);
});

it('leaves the date and time empty without a date', function () {
    Cms::config()->timezone = 'Asia/Tokyo';

    expect(ReleaseDateGroup::dateTimeValue(null))->toBe([
        'date' => '',
        'time' => '',
        'timezone' => 'Asia/Tokyo',
    ]);
});
