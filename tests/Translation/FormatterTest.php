<?php

declare(strict_types=1);

use CraftCms\Cms\Support\Facades\I18N;
use CraftCms\Cms\Translation\Formatter;

beforeEach(function () {
    $this->formatter = app(Formatter::class);
});

test('asInteger', function (mixed $value, string $output, ?string $locale = null) {
    $this->formatter->locale = $locale;

    expect($this->formatter->asInteger($value))->toBe($output);
})->with([
    [null, ''],
    ['1', '1'],
    [1, '1'],
    ['1.0', '1'],
    [1.0, '1'],
    [1.4, '1'],
    [1.5, '1'],
    [1.6, '1'],
    [1000, '1,000'],
    [1000, '1.000', 'nl'],
]);

test('asDecimal', function (mixed $input, string $output, int $decimals = 2, string $locale = 'en-US') {
    $this->formatter->locale = $locale;

    expect($this->formatter->asDecimal($input, $decimals))->toBe($output);
})->with([
    ['', '0.00'],
    ['1', '1.00'],
    ['1.0', '1.00'],
    ['87654321098765436', '87,654,321,098,765,436.00'],
    ['95836208451783051.864', '95,836,208,451,783,051.86'],
    ['95836208451783051.864', '95,836,208,451,783,052', 0],
    [1, '1.00'],
    [0.1, '0.10'],
    [0.1, '0.100', 3],
    [1000, '1,000.00', 2],
    ['1', '1,00', 2, 'nl'],
    [1000, '1.000,00', 2, 'nl'],
]);

test('asCurrency', function (mixed $input, string $output, ?string $currency = null, ?string $locale = null, bool $stripZeros = false) {
    $this->formatter->locale = $locale;

    expect($this->formatter->asCurrency($input, $currency, stripZeros: $stripZeros))->toBe($output);
})->with([
    ['', '$0.00', 'USD'],
    ['10', '$10.00', 'USD'],
    [299, '$299.00'],
    [299, '€ 299,00', null, 'nl-BE'],
    ['10', '€10.00', 'EUR'],
    ['10', '€ 10,00', 'EUR', 'nl'],
    ['10', '€ 10', 'EUR', 'nl', true],
]);

test('asTime', function (int|string|DateTime $input, string $output, ?string $format = null, ?string $locale = null) {
    $this->formatter->locale = $locale;
    $this->formatter->timeZone = 'UTC';

    expect($this->formatter->asTime($input, $format))->toBe($output);
})->with(function () {
    $dateTime = new DateTime('2021-01-01 00:00:00');

    return [
        [$dateTime, '12:00:00 AM'],
        [$dateTime, '12:00 AM', 'short'],
        [$dateTime, '12:00:00 AM', 'medium'],
        [$dateTime, '12:00:00 AM UTC', 'long'],
        [$dateTime, '12:00:00 AM Coordinated Universal Time', 'full'],
        [$dateTime, '00:00', 'HH:mm'],
        [$dateTime, '00:00:00', null, 'nl'],
        [$dateTime, '00:00', 'short', 'nl'],
        [$dateTime, '00:00:00', 'medium', 'nl'],
        [$dateTime, '00:00:00 UTC', 'long', 'nl'],
        [$dateTime, '00:00:00 gecoördineerde wereldtijd', 'full', 'nl'],
        [$dateTime, '00:00', 'HH:mm', 'nl'],
        [$dateTime, '12:00:00', 'php:h:i:s'],
        [$dateTime, '00:00:00', 'php:H:i:s'],
    ];
});

test('asTime with Locale', function (int|string|DateTime $input, string $output, ?string $locale = null) {
    $formatter = new \CraftCms\Cms\Translation\Locale($locale ?? I18N::getLocale()->id)->getFormatter();

    expect($formatter->asTime($input))->toBe($output);
})->with(function () {
    $dateTime = new DateTime('2021-01-01 00:00:00');

    return [
        [$dateTime, '4:00:00 PM'],
        [$dateTime, '16:00:00', 'nl'],
    ];
});

test('asDate', function (int|string|DateTime $input, string $output, ?string $format = null, ?string $locale = null) {
    $this->formatter->locale = $locale;
    $this->formatter->timeZone = 'UTC';

    expect($this->formatter->asDate($input, $format))->toBe($output);
})->with(function () {
    $dateTime = new DateTime('2021-01-01 00:00:00');

    return [
        [$dateTime, 'Jan 1, 2021'],
        [$dateTime, '1/1/21', 'short'],
        [$dateTime, 'Jan 1, 2021', 'medium'],
        [$dateTime, 'January 1, 2021', 'long'],
        [$dateTime, 'Friday, January 1, 2021', 'full'],
        [$dateTime, '00:00', 'HH:mm'],
        [$dateTime, '1 jan 2021', null, 'nl'],
        [$dateTime, '01-01-2021', 'short', 'nl'],
        [$dateTime, '1 jan 2021', 'medium', 'nl'],
        [$dateTime, '1 januari 2021', 'long', 'nl'],
        [$dateTime, 'vrijdag 1 januari 2021', 'full', 'nl'],
        [$dateTime, '00:00', 'HH:mm', 'nl'],
        [$dateTime, '12:00:00', 'php:h:i:s'],
        [$dateTime, '00:00:00', 'php:H:i:s'],

        // https://github.com/craftcms/cms/issues/16953
        [new DateTime('1881-01-07 00:00:00', new DateTimeZone('Europe/Amsterdam')), '1/7/81', 'short'],
        [new DateTime('1881-01-07 00:00:00', new DateTimeZone('Europe/Amsterdam')), '07-01-1881', 'short', 'nl'],
    ];
});

test('asDate with Locale', function (int|string|DateTime $input, string $output, ?string $locale = null) {
    $formatter = new \CraftCms\Cms\Translation\Locale($locale ?? I18N::getLocale()->id)->getFormatter();

    expect($formatter->asDate($input))->toBe($output);
})->with(function () {
    $dateTime = new DateTime('2021-01-01 00:00:00');

    return [
        [$dateTime, 'Dec 31, 2020'],
        [$dateTime, '31 dec 2020', 'nl'],
    ];
});

test('asDateTime', function (int|string|DateTime $input, string $output, ?string $format = null, ?string $locale = null) {
    $this->formatter->locale = $locale;
    $this->formatter->timeZone = 'UTC';

    expect($this->formatter->asDateTime($input, $format))->toBe($output);
})->with(function () {
    $dateTime = new DateTime('2021-01-01 00:00:00');

    return [
        [$dateTime, 'Jan 1, 2021, 12:00:00 AM'],
        [$dateTime, '1/1/21, 12:00 AM', 'short'],
        [$dateTime, 'Jan 1, 2021, 12:00:00 AM', 'medium'],
        [$dateTime, 'January 1, 2021 at 12:00:00 AM UTC', 'long'],
        [$dateTime, 'Friday, January 1, 2021 at 12:00:00 AM Coordinated Universal Time', 'full'],
        [$dateTime, '00:00', 'HH:mm'],
        [$dateTime, '1 jan 2021, 00:00:00', null, 'nl'],
        [$dateTime, '01-01-2021, 00:00', 'short', 'nl'],
        [$dateTime, '1 jan 2021, 00:00:00', 'medium', 'nl'],
        [$dateTime, '1 januari 2021 om 00:00:00 UTC', 'long', 'nl'],
        [$dateTime, 'vrijdag 1 januari 2021 om 00:00:00 gecoördineerde wereldtijd', 'full', 'nl'],
        [$dateTime, '00:00', 'HH:mm', 'nl'],
        [$dateTime, '12:00:00', 'php:h:i:s'],
        [$dateTime, '00:00:00', 'php:H:i:s'],
    ];
});

test('asDateTime with Locale', function (int|string|DateTime $input, string $output, ?string $locale = null) {
    $formatter = new \CraftCms\Cms\Translation\Locale($locale ?? I18N::getLocale()->id)->getFormatter();

    expect($formatter->asDateTime($input))->toBe($output);
})->with(function () {
    $dateTime = new DateTime('2021-01-01 00:00:00');

    return [
        [$dateTime, 'Dec 31, 2020, 4:00:00 PM'],
        [$dateTime, '31 dec 2020, 16:00:00', 'nl'],
    ];
});

test('asDuration', function () {
    $interval_0_seconds = new DateInterval('PT0S');
    $interval_1_second = new DateInterval('PT1S');
    $interval_244_seconds = new DateInterval('PT244S');
    $interval_1_minute = new DateInterval('PT1M');
    $interval_33_minutes = new DateInterval('PT33M');
    $interval_1_hour = new DateInterval('PT1H');
    $interval_6_hours = new DateInterval('PT6H');
    $interval_1_day = new DateInterval('P1D');
    $interval_89_days = new DateInterval('P89D');
    $interval_1_month = new DateInterval('P1M');
    $interval_5_months = new DateInterval('P5M');
    $interval_1_year = new DateInterval('P1Y');
    $interval_12_years = new DateInterval('P12Y');

    // Pass a DateInterval
    $this->assertSame('0 seconds', $this->formatter->asDuration($interval_0_seconds));
    $this->assertSame('1 second', $this->formatter->asDuration($interval_1_second));
    $this->assertSame('244 seconds', $this->formatter->asDuration($interval_244_seconds));
    $this->assertSame('1 minute', $this->formatter->asDuration($interval_1_minute));
    $this->assertSame('33 minutes', $this->formatter->asDuration($interval_33_minutes));
    $this->assertSame('1 hour', $this->formatter->asDuration($interval_1_hour));
    $this->assertSame('6 hours', $this->formatter->asDuration($interval_6_hours));
    $this->assertSame('1 day', $this->formatter->asDuration($interval_1_day));
    $this->assertSame('89 days', $this->formatter->asDuration($interval_89_days));
    $this->assertSame('1 month', $this->formatter->asDuration($interval_1_month));
    $this->assertSame('5 months', $this->formatter->asDuration($interval_5_months));
    $this->assertSame('1 year', $this->formatter->asDuration($interval_1_year));
    $this->assertSame('12 years', $this->formatter->asDuration($interval_12_years));

    // Pass a numeric value
    $this->assertSame('0 seconds', $this->formatter->asDuration(0));
    $this->assertSame('1 second', $this->formatter->asDuration(1));
    $this->assertSame('4 minutes, 4 seconds', $this->formatter->asDuration(244));
    $this->assertSame('1 minute', $this->formatter->asDuration(60));
    $this->assertSame('33 minutes', $this->formatter->asDuration(1980));
    $this->assertSame('1 hour', $this->formatter->asDuration(3600));
    $this->assertSame('6 hours', $this->formatter->asDuration(21600));
    $this->assertSame('1 day', $this->formatter->asDuration(86400));

    // Pass a DateInterval string
    $this->assertSame('1 year, 2 months, 10 days, 2 hours, 30 minutes', $this->formatter->asDuration('2007-03-01T13:00:00Z/2008-05-11T15:30:00Z'));
    $this->assertSame('1 year, 2 months, 10 days, 2 hours, 30 minutes', $this->formatter->asDuration('2007-03-01T13:00:00Z/P1Y2M10DT2H30M'));
    $this->assertSame('1 year, 2 months, 10 days, 2 hours, 30 minutes', $this->formatter->asDuration('P1Y2M10DT2H30M/2008-05-11T15:30:00Z'));
    $this->assertSame('1 year, 2 months, 10 days, 2 hours, 30 minutes', $this->formatter->asDuration('P1Y2M10DT2H30M'));
    $this->assertSame('-1 year, 2 months, 10 days, 2 hours, 30 minutes', $this->formatter->asDuration('P-1Y2M10DT2H30M'));
    $this->assertSame('94 months', $this->formatter->asDuration('P94M'));
    $this->assertSame('-94 months', $this->formatter->asDuration('P-94M'));

    // Invert all the DateIntervals
    $interval_0_seconds->invert = true;
    $interval_1_second->invert = true;
    $interval_244_seconds->invert = true;
    $interval_1_minute->invert = true;
    $interval_33_minutes->invert = true;
    $interval_1_hour->invert = true;
    $interval_6_hours->invert = true;
    $interval_1_day->invert = true;
    $interval_89_days->invert = true;
    $interval_1_month->invert = true;
    $interval_5_months->invert = true;
    $interval_1_year->invert = true;
    $interval_12_years->invert = true;

    // Pass a inverted DateInterval
    $this->assertSame('0 seconds', $this->formatter->asDuration($interval_0_seconds));
    $this->assertSame('-1 second', $this->formatter->asDuration($interval_1_second));
    $this->assertSame('-244 seconds', $this->formatter->asDuration($interval_244_seconds));
    $this->assertSame('-1 minute', $this->formatter->asDuration($interval_1_minute));
    $this->assertSame('-33 minutes', $this->formatter->asDuration($interval_33_minutes));
    $this->assertSame('-1 hour', $this->formatter->asDuration($interval_1_hour));
    $this->assertSame('-6 hours', $this->formatter->asDuration($interval_6_hours));
    $this->assertSame('-1 day', $this->formatter->asDuration($interval_1_day));
    $this->assertSame('-89 days', $this->formatter->asDuration($interval_89_days));
    $this->assertSame('-1 month', $this->formatter->asDuration($interval_1_month));
    $this->assertSame('-5 months', $this->formatter->asDuration($interval_5_months));
    $this->assertSame('-1 year', $this->formatter->asDuration($interval_1_year));
    $this->assertSame('-12 years', $this->formatter->asDuration($interval_12_years));

    // other options
    $this->assertSame('minus 244 seconds', $this->formatter->asDuration($interval_244_seconds, ' and ', 'minus '));
    $this->assertSame('minus 4 minutes and 4 seconds', $this->formatter->asDuration(-244, ' and ', 'minus '));
    $this->assertSame('1 second', $this->formatter->asDuration(1.5));

    // Pass a inverted DateInterval string
    $this->assertSame('-1 year, 2 months, 10 days, 2 hours, 30 minutes', $this->formatter->asDuration('2008-05-11T15:30:00Z/2007-03-01T13:00:00Z'));

    // null display
    $this->assertSame('', $this->formatter->asDuration(null));
});

test('asTimestamp', function (mixed $input, string $output, ?string $locale = null) {
    $this->formatter->locale = $locale;
    config()->set('app.timezone', 'UTC');

    expect($this->formatter->asTimestamp($input))->toBe($output);
})->with(function () {
    $dateTime = new DateTime(timezone: new DateTimeZone('UTC'));
    $dateTime->setTime(12, 0);

    return [
        [(clone $dateTime)->format('Y-m-d'), 'Today'],
        [(clone $dateTime)->setTime(17, 0)->format('Y-m-d H:i:s'), '5:00:00 PM'],
        [(clone $dateTime)->sub(DateInterval::createFromDateString('24 hours'))->format('Y-m-d'), 'Yesterday'],
        [(clone $dateTime)->sub(DateInterval::createFromDateString('24 hours'))->format('Y-m-d H:i:s'), 'Yesterday'],
        [(clone $dateTime)->format('Y-m-d'), 'Vandaag', 'nl'],
        [(clone $dateTime)->format('Y-m-d 00:00:00'), '00:00:00', 'nl'],
        [(clone $dateTime)->sub(new DateInterval('P1D'))->format('Y-m-d'), 'Gisteren', 'nl'],
        [(clone $dateTime)->sub(new DateInterval('P1D'))->format('Y-m-d 00:00:00'), 'Gisteren', 'nl'],
        [new DateTime('2021-01-01 00:00:00'), 'Jan 1, 2021'],
    ];
});

test('asSize', function (string|int|float|null $input, string $output, int $sizeFormatBase = 1024, ?int $decimals = null) {
    $this->formatter->sizeFormatBase = $sizeFormatBase;

    expect($this->formatter->asSize($input, $decimals))->toBe($output);
})->with([
    [null, ''],
    [0, '0 bytes'],
    [1, '1 byte'],
    [10, '10 bytes'],
    [100, '100 bytes'],
    [1000, '1,000 bytes'],
    [10000, '9.766 kibibytes'],
    [100000, '97.656 kibibytes'],
    [1000000, '976.562 kibibytes'],
    [10000000, '9.537 mebibytes'],
    [100000000, '95.367 mebibytes'],
    [1000000000, '953.674 mebibytes'],
    [10000000000, '9.313 gibibytes'],
    [100000000000, '93.132 gibibytes'],
    [1000000000000, '931.323 gibibytes'],

    [0, '0 bytes', 1000],
    [1, '1 byte', 1000],
    [10, '10 bytes', 1000],
    [100, '100 bytes', 1000],
    [1000, '1 kilobyte', 1000],
    [10000, '10 kilobytes', 1000],
    [100000, '100 kilobytes', 1000],
    [1000000, '1 megabyte', 1000],
    [10000000, '10 megabytes', 1000],
    [100000000, '100 megabytes', 1000],
    [1000000000, '1 gigabyte', 1000],
    [10000000000, '10 gigabytes', 1000],
    [100000000000, '100 gigabytes', 1000],
    [1000000000000, '1 terabyte', 1000],

    [0, '0 bytes', 1000, 2],
    [1, '1 byte', 1000, 2],
    [10, '10 bytes', 1000, 2],
    [100, '100 bytes', 1000, 2],
    [1000, '1.00 kilobyte', 1000, 2],
    [10000, '10.00 kilobytes', 1000, 2],
    [100000, '100.00 kilobytes', 1000, 2],
    [1000000, '1.00 megabyte', 1000, 2],
    [10000000, '10.00 megabytes', 1000, 2],
    [100000000, '100.00 megabytes', 1000, 2],
    [1000000000, '1.00 gigabyte', 1000, 2],
    [10000000000, '10.00 gigabytes', 1000, 2],
    [100000000000, '100.00 gigabytes', 1000, 2],
    [1000000000000, '1.00 terabyte', 1000, 2],
]);

test('asShortSize', function (string|int|float|null $input, string $output, int $sizeFormatBase = 1024, ?int $decimals = null) {
    $this->formatter->sizeFormatBase = $sizeFormatBase;

    expect($this->formatter->asShortSize($input, $decimals))->toBe($output);
})->with([
    [null, ''],
    [0, '0 B'],
    [1, '1 B'],
    [10, '10 B'],
    [100, '100 B'],
    [1000, '1,000 B'],
    [10000, '9.766 KIB'],
    [100000, '97.656 KIB'],
    [1000000, '976.562 KIB'],
    [10000000, '9.537 MIB'],
    [100000000, '95.367 MIB'],
    [1000000000, '953.674 MIB'],
    [10000000000, '9.313 GIB'],
    [100000000000, '93.132 GIB'],
    [1000000000000, '931.323 GIB'],

    [0, '0 B', 1000],
    [1, '1 B', 1000],
    [10, '10 B', 1000],
    [100, '100 B', 1000],
    [1000, '1 KB', 1000],
    [10000, '10 KB', 1000],
    [100000, '100 KB', 1000],
    [1000000, '1 MB', 1000],
    [10000000, '10 MB', 1000],
    [100000000, '100 MB', 1000],
    [1000000000, '1 GB', 1000],
    [10000000000, '10 GB', 1000],
    [100000000000, '100 GB', 1000],
    [1000000000000, '1 TB', 1000],

    [0, '0 B', 1000, 2],
    [1, '1 B', 1000, 2],
    [10, '10 B', 1000, 2],
    [100, '100 B', 1000, 2],
    [1000, '1.00 KB', 1000, 2],
    [10000, '10.00 KB', 1000, 2],
    [100000, '100.00 KB', 1000, 2],
    [1000000, '1.00 MB', 1000, 2],
    [10000000, '10.00 MB', 1000, 2],
    [100000000, '100.00 MB', 1000, 2],
    [1000000000, '1.00 GB', 1000, 2],
    [10000000000, '10.00 GB', 1000, 2],
    [100000000000, '100.00 GB', 1000, 2],
    [1000000000000, '1.00 TB', 1000, 2],
]);

test('asPercent', function (mixed $input, string $output, ?string $locale = null) {
    $this->formatter->locale = $locale;

    expect($this->formatter->asPercent($input))->toBe($output);
})->with([
    [null, '0%'],
    ['', '0%'],
    ['0', '0%'],
    [0, '0%'],
    [1, '100%'],
    ['0.81', '81%'],
    ['87654321098765436', '8,765,432,109,876,543,600%'],
    ['95836208451783051.864', '9,583,620,845,178,305,186%'],
    ['95836208451783051.328', '9,583,620,845,178,305,133%'],
]);

test('willBeMisrepresented', function (mixed $input, bool $output, ?string $locale = null) {
    $this->formatter->locale = $locale;

    expect($this->formatter->willBeMisrepresented($input))->toBe($output);
})->with([
    [null, false],
    ['', false],
    [1, false],
    ['9.00', false],
    ['87654321098765436', true],
]);
