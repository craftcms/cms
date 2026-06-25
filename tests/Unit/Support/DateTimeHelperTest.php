<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Support\DateTimeHelper;

function supportSystemDateTime(string $dateTime = '2018-08-09 20:00:00'): DateTime
{
    $systemTimeZone = new DateTimeZone(Cms::timezone());
    $date = new DateTime($dateTime, new DateTimeZone('UTC'));
    $date->setTimezone($systemTimeZone);

    return $date;
}

function supportSystemDateTimeAtMidnight(): DateTime
{
    return supportSystemDateTime('2018-08-09 00:00:00');
}

function supportTokyoToSystemDateTime(): DateTime
{
    $date = new DateTime('2018-08-09 20:00:00', new DateTimeZone('Asia/Tokyo'));
    $date->setTimezone(new DateTimeZone(Cms::timezone()));

    return $date;
}

beforeEach(function () {
    $this->systemTimeZone = new DateTimeZone(Cms::timezone());
    $this->utcTimeZone = new DateTimeZone('UTC');
    $this->asiaTokyoTimeZone = new DateTimeZone('Asia/Tokyo');

    Cms::config()->defaultWeekStartDay = 1;
});

afterEach(function () {
    Cms::config()->defaultWeekStartDay = 1;

    foreach (range(1, 6) as $_) {
        DateTimeHelper::resume();
    }
});

describe('constants', function () {
    test('exposes relative time units for template parsers', function () {
        expect(DateTimeHelper::RELATIVE_TIME_UNITS)
            ->toContain('seconds')
            ->toContain('weeks')
            ->toContain('forthnights');
    });
});

describe('pause and Laravel clock', function () {
    test('pauses and resumes current time', function () {
        $now = now()->subMinute();
        $timestamp = $now->getTimestamp();

        DateTimeHelper::pause($now);
        expect(now()->getTimestamp())->toBe($timestamp);

        DateTimeHelper::pause();
        expect(now()->getTimestamp())->toBe($timestamp);

        DateTimeHelper::resume();
        expect(now()->getTimestamp())->toBe($timestamp);

        DateTimeHelper::resume();
        expect(now()->getTimestamp())->not->toBe($timestamp);
    });
});

describe('toDateTime', function () {
    test('converts supported values', function (callable|DateTime|false $expected, mixed $value) {
        if (is_callable($expected)) {
            $expected = $expected();
        }

        if (is_callable($value)) {
            $value = $value();
        }

        $date = DateTimeHelper::toDateTime($value);

        if ($expected === false) {
            expect($date)->toBeFalse();

            return;
        }

        expect($date)->toBeInstanceOf(DateTime::class)
            ->and(abs($date->getTimestamp() - $expected->getTimestamp()))->toBeLessThanOrEqual(1);
    })->with([
        'timestamp' => [new DateTime('@1625575906'), 1625575906],
        'now' => [fn () => new DateTime, 'now'],
        'no params' => [false, ['date' => '', 'time' => '']],
        'invalid separator' => [false, '2018.08.09 20:00:00'],
        'null type' => [false, null],
        'empty string' => [false, ''],
        'empty array' => [false, []],
        'year' => [fn () => new DateTime('2021-01-01 00:00:00', new DateTimeZone('UTC')), '2021'],
        'datetime with timezone' => [fn () => new DateTime('2021-09-01T12:00', new DateTimeZone('Europe/Berlin')), ['datetime' => '2021-09-01T12:00', 'timezone' => 'Europe/Berlin']],
        'datetime immutable' => [fn () => new DateTime('@1625575906'), fn () => new DateTimeImmutable('@1625575906')],
        'other locale' => [new DateTime('2023-09-26 00:00:00', new DateTimeZone('UTC')), ['date' => '26/9/2023', 'locale' => 'en-GB']],
        'constructor' => [new DateTime('2am', new DateTimeZone('America/Los_Angeles')), '2am'],
    ]);

    test('converts values in explicit timezones to the system timezone', function (mixed $value, callable $expectedResult) {
        $expectedDate = $expectedResult();
        $date = DateTimeHelper::toDateTime($value);

        expect($date)->toBeInstanceOf(DateTime::class)
            ->and($date?->getTimezone()->getName())->toBe($this->systemTimeZone->getName())
            ->and($expectedDate->getTimezone()->getName())->toBe($this->systemTimeZone->getName())
            ->and($date?->format('Y-m-d H:i:s'))->toBe($expectedDate->format('Y-m-d H:i:s'));
    })->with([
        'array format' => [
            ['date' => '08-09-2018', 'time' => '08:00 PM', 'timezone' => 'Asia/Tokyo'],
            supportTokyoToSystemDateTime(...),
        ],
        'w3c format' => [
            '2018-08-09T20:00:00+09:00',
            supportTokyoToSystemDateTime(...),
        ],
    ]);

    test('defaults to utc when system timezone conversion is disabled', function (mixed $value) {
        if (is_callable($value)) {
            $value = $value();
        }

        $date = DateTimeHelper::toDateTime($value, false, false);

        expect($date)->toBeInstanceOf(DateTime::class)
            ->and($date?->getTimezone()->getName())->toBe($this->utcTimeZone->getName());
    })->with([
        'mysql' => '2018-08-08 20:00:00',
        'array' => fn () => ['date' => '08-09-2018', 'time' => '08:00 PM'],
        'w3c format' => '2018-08-09T20:00:00',
        'datetime object' => new DateTime('2018-08-09', new DateTimeZone('UTC')),
    ]);

    test('respects passed timezone when system timezone conversion is disabled', function (mixed $value, DateTime $expectedDate, DateTimeZone $expectedTimeZone) {
        $date = DateTimeHelper::toDateTime($value, false, false);

        expect($date)->toBeInstanceOf(DateTime::class)
            ->and($date?->getTimezone()->getName())->toBe($expectedTimeZone->getName())
            ->and($expectedDate->getTimezone()->getName())->toBe($expectedTimeZone->getName())
            ->and($date?->format('Y-m-d H:i:s'))->toBe($expectedDate->format('Y-m-d H:i:s'));
    })->with([
        'mysql format' => [
            '2018-08-09 20:00:00',
            new DateTime('2018-08-09 20:00:00', new DateTimeZone('UTC')),
            new DateTimeZone('UTC'),
        ],
        'array format' => [
            ['date' => '08-09-2018', 'time' => '08:00 PM', 'timezone' => 'Asia/Tokyo'],
            new DateTime('2018-08-09 20:00:00', new DateTimeZone('Asia/Tokyo')),
            new DateTimeZone('Asia/Tokyo'),
        ],
        'w3c format' => [
            '2018-08-09T20:00:00+09:00',
            new DateTime('2018-08-09 20:00:00', new DateTimeZone('+09:00')),
            new DateTimeZone('+09:00'),
        ],
        'zulu timezone' => [
            '2018-08-09T20:00:00Z',
            new DateTime('2018-08-09 20:00:00', new DateTimeZone('UTC')),
            new DateTimeZone('UTC'),
        ],
        'abbreviated timezone' => [
            '2018-08-09 20:00:00 CEST',
            new DateTime('2018-08-09 20:00:00', new DateTimeZone('Europe/Berlin')),
            new DateTimeZone('Europe/Berlin'),
        ],
    ]);

    test('creates expected dates for common formats', function (mixed $value, callable $expectedResult) {
        if (is_callable($value)) {
            $value = $value();
        }

        $expectedDate = $expectedResult();
        $date = DateTimeHelper::toDateTime($value);

        expect($date)->toBeInstanceOf(DateTime::class)
            ->and($date?->format('Y-m-d H:i:s'))->toBe($expectedDate->format('Y-m-d H:i:s'));
    })->with([
        'invalid date valid time' => [fn () => ['date' => '2018-08-09', 'time' => '08:00 PM'], supportSystemDateTime(...)],
        'invalid date format' => [fn () => ['date' => '2018-08-09'], supportSystemDateTimeAtMidnight(...)],
        'basic mysql format' => ['2018-08-09 20:00:00', supportSystemDateTime(...)],
        'array diff separator slash' => [fn () => ['date' => '08/09/2018', 'time' => '08:00 PM'], supportSystemDateTime(...)],
        'array diff separator dot' => [fn () => ['date' => '08.09.2018', 'time' => '08:00 PM'], supportSystemDateTime(...)],
        'array format' => [fn () => ['date' => '08-09-2018', 'time' => '08:00 PM'], supportSystemDateTime(...)],
        'dotted meridiem' => [fn () => ['date' => '08-09-2018', 'time' => '08:00 p.m.'], supportSystemDateTime(...)],
        'w3c format' => ['2018-08-09T20:00:00', supportSystemDateTime(...)],
        'unix timestamp' => ['1533844800', supportSystemDateTime(...)],
    ]);

    test('defaults empty array date to today', function () {
        $date = DateTimeHelper::toDateTime(['date' => '', 'time' => '08:00PM']);

        $created = new DateTime('now', $this->utcTimeZone);
        $expectedDate = new DateTime($created->format('Y-m-d').' 20:00:00', $this->utcTimeZone);
        $expectedDate->setTimezone($this->systemTimeZone);

        expect($date?->format('Y-m-d H:i:s'))->toBe($expectedDate->format('Y-m-d H:i:s'));
    });
});

describe('normalization and formatting helpers', function () {
    test('normalizes timezones', function (string|false $expected, string $timeZone) {
        expect(DateTimeHelper::normalizeTimeZone($timeZone))->toBe($expected);
    })->with([
        'est' => ['America/New_York', 'EST'],
        'cest' => ['Europe/Berlin', 'CEST'],
        'numeric no colon' => ['+09:00', '+0900'],
        'numeric with colon' => ['-02:00', '-02:00'],
        'utc' => ['UTC', 'UTC'],
        'gmt' => ['UTC', 'GMT'],
        'iana' => ['Europe/Amsterdam', 'Europe/Amsterdam'],
        'invalid' => [false, 'NotATz'],
    ]);

    test('returns timezone abbreviations', function (string $expected, string|DateTimeZone $timeZone, ?DateTimeInterface $date = null) {
        expect(DateTimeHelper::timeZoneAbbreviation($timeZone, $date))->toBe($expected);
    })->with([
        'utc string' => ['UTC', 'UTC'],
        'region daylight time' => ['CEST', 'Europe/Berlin', new DateTimeImmutable('2024-07-01 12:00:00', new DateTimeZone('UTC'))],
        'region standard time' => ['CET', 'Europe/Berlin', new DateTimeImmutable('2024-01-01 12:00:00', new DateTimeZone('UTC'))],
        'offset string' => ['+09:00', '+09:00'],
        'offset object' => ['+09:00', new DateTimeZone('+09:00')],
        'abbreviation object' => ['EST', new DateTimeZone('EST')],
    ]);

    test('detects iso8601 values', function (bool $expected, mixed $value) {
        expect(DateTimeHelper::isIso8601($value))->toBe($expected);
    })->with([
        'atom' => [true, '2018-09-30T13:41:06+00:00'],
        'iso8601' => [true, '2018-09-30T13:41:06+0000'],
        'zulu' => [false, '2018-09-30T13:41:06Z'],
        'fractional seconds' => [false, '2018-09-30T13:41:06.123+00:00'],
        'placeholder' => [false, 'YYYY-MM-DDTHH:MM:SS+HH:MM'],
        'date only' => [false, '2008-09-15'],
        'plain string' => [false, 'I am not a string'],
        'datetime object' => [false, new DateTime('2018-09-21')],
        'false' => [false, false],
        'null' => [false, null],
    ]);

    test('formats iso8601 values', function (string|false $expected, mixed $date) {
        expect(DateTimeHelper::toIso8601($date))->toBe($expected);
    })->with((function () {
        $amsterdamTime = new DateTime('2018-08-08 20:00:00', new DateTimeZone('Europe/Amsterdam'));
        $tokyoTime = new DateTime('2018-08-08 20:00:00', new DateTimeZone('Asia/Tokyo'));

        return [
            'tokyo' => ['2018-08-08T20:00:00+09:00', $tokyoTime],
            'amsterdam' => ['2018-08-08T20:00:00+02:00', $amsterdamTime],
            'invalid returns false' => [false, ['date' => '']],
        ];
    })());
});

describe('human readable durations', function () {
    test('formats human durations', function (string $expected, string|int $duration, ?bool $showSeconds = null, ?string $language = null) {
        expect(DateTimeHelper::humanDuration($duration, $showSeconds, $language))->toBe($expected);
    })->with([
        'one day' => ['1 day', 'P1D'],
        'one year' => ['1 year', 'P1Y'],
        'one month' => ['1 month', 'P1M'],
        'one hour' => ['1 hour', 'PT1H'],
        'one second' => ['1 second', 'PT1S'],
        'mixed interval' => ['2 months, 1 day, and 1 hour', 'P2M1DT1H'],
        'omit seconds rounded down' => ['1 hour and 1 minute', 'PT1H1M25S', false],
        'omit seconds rounded up' => ['1 hour and 2 minutes', 'PT1H1M55S', false],
        'omit seconds under minute' => ['less than a minute', 'PT1S', false],
        'eighty two default' => ['1 minute', 82],
        'eighty two no seconds' => ['1 minute', 82, false],
        'eighty two with seconds' => ['1 minute and 22 seconds', 82, true],
        'twenty two with seconds' => ['22 seconds', 22, true],
        'twenty two default' => ['22 seconds', 22],
        'twenty two no seconds' => ['less than a minute', 22, false],
        'one second numeric' => ['1 second', 1],
        'two minutes' => ['2 minutes', 120],
        'two minutes five seconds' => ['2 minutes and 5 seconds', 125, true],
        'two minutes one second' => ['2 minutes and 1 second', 121, true],
        'two minutes without seconds' => ['2 minutes', 121, false],
        'three minutes rounded' => ['3 minutes', 179, false],
        'hour numeric' => ['1 hour', 3600],
        'day numeric' => ['1 day', 86400],
        'week numeric' => ['1 week', 604800],
        'eight days' => ['8 days', 691200],
        'nine hundred ninety nine seconds rounded' => ['17 minutes', 999],
        'string numeric seconds rounded' => ['17 minutes', '999'],
        'nine hundred ninety nine with seconds' => ['16 minutes and 39 seconds', 999, true],
        'pt999s' => ['999 seconds', 'PT999S'],
        'overflow seconds' => ['27 minutes', 'PT10M999S'],
        'zero default' => ['0 seconds', 0],
        'zero no seconds' => ['less than a minute', 0, false],
        'thousand years' => ['1,000 years', 'P1000Y', false],
        'thousand years french' => ['1'."\u{202f}".'000 ans', 'P1000Y', false, 'fr'],
    ]);
});

describe('interval helpers', function () {
    test('creates date intervals from seconds', function (string $expected, int $input) {
        $interval = DateTimeHelper::toDateInterval($input);

        expect($interval->format('%r%a:%h:%i:%s'))->toBe($expected);
    })->with([
        'positive' => ['0:0:0:10', 10],
        'negative' => ['-0:0:0:10', -10],
    ]);

    test('returns false for zero date interval input', function () {
        expect(DateTimeHelper::toDateInterval(0))->toBeFalse();
    });
});

describe('timestamps and times', function () {
    test('validates timestamps', function (bool $expected, mixed $timestamp) {
        expect(DateTimeHelper::isValidTimeStamp($timestamp))->toBe($expected);
    })->with((function () {
        $amsterdamTime = new DateTime('2018-12-30 20:00:00', new DateTimeZone('Europe/Amsterdam'));
        $tokyoTime = new DateTime('2018-12-30 20:00:00', new DateTimeZone('Asia/Tokyo'));

        return [
            'amsterdam timestamp' => [true, $amsterdamTime->getTimestamp()],
            'tokyo timestamp' => [true, $tokyoTime->getTimestamp()],
            'numeric string' => [true, '1539520249'],
            'zero' => [true, 0],
            'iso string' => [false, '2018-10-14T21:30:49+09:00'],
            'true' => [false, true],
            'string' => [false, 'string'],
            'null' => [false, null],
            'false' => [false, false],
        ];
    })());

    test('converts times to seconds', function (?int $expected, int|string|DateTimeInterface|null $time) {
        expect(DateTimeHelper::timeToSeconds($time))->toBe($expected);
    })->with([
        'int hour' => [3600, 3600],
        'int hour minute' => [3660, 3660],
        'int full' => [3661, 3661],
        'string hour' => [3600, '01'],
        'string hour minute' => [3660, '01:01'],
        'string full' => [3661, '01:01:01'],
        'datetime utc' => [3661, new DateTime('2026-05-17 01:01:01', new DateTimeZone('UTC'))],
        'datetime los angeles' => [3661, new DateTime('2026-05-17 01:01:01', new DateTimeZone('America/Los_Angeles'))],
        'null' => [null, null],
    ]);

    test('returns the configured first weekday', function () {
        expect(DateTimeHelper::firstWeekDay())->toBe(1);
    });
});
