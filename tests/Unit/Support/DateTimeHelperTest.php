<?php

declare(strict_types=1);

use craft\helpers\DateTimeHelper as LegacyDateTimeHelper;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Support\DateTimeHelper;

function supportSystemDateTime(string $dateTime = '2018-08-09 20:00:00'): DateTime
{
    $systemTimeZone = new DateTimeZone(app()->getTimezone());
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
    $date->setTimezone(new DateTimeZone(app()->getTimezone()));

    return $date;
}

beforeEach(function () {
    config()->set('app.timezone', 'America/Los_Angeles');

    $this->systemTimeZone = new DateTimeZone(app()->getTimezone());
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
    test('exposes expected time constants', function (int $expected, int $actual) {
        expect($actual)->toBe($expected);
    })->with([
        'day' => [86400, DateTimeHelper::SECONDS_DAY],
        'hour' => [3600, DateTimeHelper::SECONDS_HOUR],
        'minute' => [60, DateTimeHelper::SECONDS_MINUTE],
        'month' => [2629740, DateTimeHelper::SECONDS_MONTH],
        'year' => [31556874, DateTimeHelper::SECONDS_YEAR],
    ]);
});

describe('pause and date anchors', function () {
    test('pauses and resumes current time', function () {
        $now = new DateTime('now')->modify('-1 minute');
        $timestamp = $now->getTimestamp();

        DateTimeHelper::pause($now);
        expect(DateTimeHelper::currentTimeStamp())->toBe($timestamp);

        DateTimeHelper::pause();
        expect(DateTimeHelper::currentTimeStamp())->toBe($timestamp);

        DateTimeHelper::resume();
        expect(DateTimeHelper::currentTimeStamp())->toBe($timestamp);

        DateTimeHelper::resume();
        expect(DateTimeHelper::currentTimeStamp())->not->toBe($timestamp);
    });

    test('returns today', function () {
        DateTimeHelper::pause(new DateTime('2024-04-06 10:43:12', $this->utcTimeZone));

        expect(DateTimeHelper::today($this->utcTimeZone))
            ->toEqual(new DateTime('2024-04-06 00:00:00', $this->utcTimeZone));
    });

    test('returns tomorrow', function () {
        DateTimeHelper::pause(new DateTime('2024-04-06 10:43:12', $this->utcTimeZone));

        expect(DateTimeHelper::tomorrow($this->utcTimeZone))
            ->toEqual(new DateTime('2024-04-07 00:00:00', $this->utcTimeZone));
    });

    test('returns yesterday', function () {
        DateTimeHelper::pause(new DateTime('2024-04-06 10:43:12', $this->utcTimeZone));

        expect(DateTimeHelper::yesterday($this->utcTimeZone))
            ->toEqual(new DateTime('2024-04-05 00:00:00', $this->utcTimeZone));
    });

    test('returns this week using configured first weekday', function () {
        DateTimeHelper::pause(new DateTime('2024-04-10 10:43:12', $this->utcTimeZone));

        expect(DateTimeHelper::firstWeekDay())->toBe(1)
            ->and(DateTimeHelper::thisWeek($this->utcTimeZone))
            ->toEqual(new DateTime('2024-04-08 00:00:00', $this->utcTimeZone));

        Cms::config()->defaultWeekStartDay = 0;

        expect(DateTimeHelper::firstWeekDay())->toBe(0)
            ->and(DateTimeHelper::thisWeek($this->utcTimeZone))
            ->toEqual(new DateTime('2024-04-07 00:00:00', $this->utcTimeZone));
    });

    test('returns next week using configured first weekday', function () {
        DateTimeHelper::pause(new DateTime('2024-04-10 10:43:12', $this->utcTimeZone));

        expect(DateTimeHelper::firstWeekDay())->toBe(1)
            ->and(DateTimeHelper::nextWeek($this->utcTimeZone))
            ->toEqual(new DateTime('2024-04-15 00:00:00', $this->utcTimeZone));

        Cms::config()->defaultWeekStartDay = 0;

        expect(DateTimeHelper::firstWeekDay())->toBe(0)
            ->and(DateTimeHelper::nextWeek($this->utcTimeZone))
            ->toEqual(new DateTime('2024-04-14 00:00:00', $this->utcTimeZone));
    });

    test('returns last week using configured first weekday', function () {
        DateTimeHelper::pause(new DateTime('2024-04-10 10:43:12', $this->utcTimeZone));

        expect(DateTimeHelper::firstWeekDay())->toBe(1)
            ->and(DateTimeHelper::lastWeek($this->utcTimeZone))
            ->toEqual(new DateTime('2024-04-01 00:00:00', $this->utcTimeZone));

        Cms::config()->defaultWeekStartDay = 0;

        expect(DateTimeHelper::firstWeekDay())->toBe(0)
            ->and(DateTimeHelper::lastWeek($this->utcTimeZone))
            ->toEqual(new DateTime('2024-03-31 00:00:00', $this->utcTimeZone));
    });

    test('returns this month', function () {
        DateTimeHelper::pause(new DateTime('2024-04-06 10:43:12', $this->utcTimeZone));

        expect(DateTimeHelper::thisMonth($this->utcTimeZone))
            ->toEqual(new DateTime('2024-04-01 00:00:00', $this->utcTimeZone));
    });

    test('returns next month', function () {
        DateTimeHelper::pause(new DateTime('2024-04-06 10:43:12', $this->utcTimeZone));

        expect(DateTimeHelper::nextMonth($this->utcTimeZone))
            ->toEqual(new DateTime('2024-05-01 00:00:00', $this->utcTimeZone));
    });

    test('returns last month', function () {
        DateTimeHelper::pause(new DateTime('2024-04-06 10:43:12', $this->utcTimeZone));

        expect(DateTimeHelper::lastMonth($this->utcTimeZone))
            ->toEqual(new DateTime('2024-03-01 00:00:00', $this->utcTimeZone));
    });

    test('returns this year', function () {
        DateTimeHelper::pause(new DateTime('2024-04-06 10:43:12', $this->utcTimeZone));

        expect(DateTimeHelper::thisYear($this->utcTimeZone))
            ->toEqual(new DateTime('2024-01-01 00:00:00', $this->utcTimeZone));
    });

    test('returns last year', function () {
        DateTimeHelper::pause(new DateTime('2024-04-06 10:43:12', $this->utcTimeZone));

        expect(DateTimeHelper::lastYear($this->utcTimeZone))
            ->toEqual(new DateTime('2023-01-01 00:00:00', $this->utcTimeZone));
    });

    test('returns next year', function () {
        DateTimeHelper::pause(new DateTime('2024-04-06 10:43:12', $this->utcTimeZone));

        expect(DateTimeHelper::nextYear($this->utcTimeZone))
            ->toEqual(new DateTime('2025-01-01 00:00:00', $this->utcTimeZone));
    });

    test('returns current utc datetime', function () {
        expect(DateTimeHelper::currentUTCDateTime()->format('Y-m-d H:i:s'))
            ->toBe(new DateTime('now', $this->utcTimeZone)->format('Y-m-d H:i:s'));
    });

    test('returns current timestamp', function () {
        expect(DateTimeHelper::currentTimeStamp())
            ->toBe(new DateTime('now', $this->utcTimeZone)->getTimestamp());
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

    test('detects iso8601 values', function (bool $expected, mixed $value) {
        expect(DateTimeHelper::isIso8601($value))->toBe($expected);
    })->with([
        'valid' => [true, '2018-09-30T13:41:06+00:00'],
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

describe('relative time helpers', function () {
    test('builds relative time statements', function (string $expected, int $number, string $unit) {
        expect(DateTimeHelper::relativeTimeStatement($number, $unit))->toBe($expected);
    })->with([
        'one day' => ['+1 day', 1, 'day'],
        'single week alias' => ['+7 days', 1, 'week'],
        'plural week one' => ['+1 weeks', 1, 'weeks'],
        'plural week two' => ['+2 weeks', 2, 'weeks'],
    ]);

    test('converts relative time statements to seconds', function (int $expected, int $number, string $unit) {
        expect([
            $expected,
            $expected + 3600,
            $expected - 3600,
        ])->toContain(DateTimeHelper::relativeTimeToSeconds($number, $unit));
    })->with([
        'hour' => [3600, 1, 'hour'],
        'week' => [604800, 1, 'week'],
    ]);
});

describe('date checks', function () {
    test('checks whether a date is today', function () {
        $date = new DateTime('now');
        expect(DateTimeHelper::isToday($date))->toBeTrue();

        $date->modify('-1 days');
        expect(DateTimeHelper::isToday($date))->toBeFalse();

        $date->modify('-1 days');
        expect(DateTimeHelper::isToday($date))->toBeFalse();

        $date->modify('+2 days');
        expect(DateTimeHelper::isToday($date))->toBeTrue();
    });

    test('checks whether a date is yesterday', function () {
        $date = new DateTime('now');
        $date->modify('-1 days');
        expect(DateTimeHelper::isYesterday($date))->toBeTrue();

        $date->modify('-1 days');
        expect(DateTimeHelper::isYesterday($date))->toBeFalse();

        $date->modify('+2 days');
        expect(DateTimeHelper::isYesterday($date))->toBeFalse();

        expect(DateTimeHelper::isYesterday(new DateTime('yesterday')))->toBeTrue();
    });

    test('checks whether a date is in this year', function () {
        $date = new DateTime('now');
        expect(DateTimeHelper::isThisYear($date))->toBeTrue();

        $date->modify('-1 years');
        expect(DateTimeHelper::isThisYear($date))->toBeFalse();

        $date->modify('+2 years');
        expect(DateTimeHelper::isThisYear($date))->toBeFalse();
    });

    test('checks whether a date is in this week', function () {
        $date = new DateTime('now');
        expect(DateTimeHelper::isThisWeek($date))->toBeTrue();

        $date->modify('-1 weeks');
        expect(DateTimeHelper::isThisWeek($date))->toBeFalse();

        $date->modify('+1 weeks');
        expect(DateTimeHelper::isThisWeek($date))->toBeTrue();

        $date->modify('+2 weeks');
        expect(DateTimeHelper::isYesterday($date))->toBeFalse();
    });

    test('checks whether a date is in the past', function () {
        $date = new DateTime('now', $this->systemTimeZone);
        $date->modify('-5 seconds');
        expect(DateTimeHelper::isInThePast($date))->toBeTrue();

        $date->modify('-1 minutes');
        expect(DateTimeHelper::isInThePast($date))->toBeTrue();

        $date->modify('+2 minutes');
        expect(DateTimeHelper::isInThePast($date))->toBeFalse();
    });

    test('checks whether a date is in this month', function () {
        $date = new DateTime('now');
        expect(DateTimeHelper::isThisMonth($date))->toBeTrue();

        $date->modify('-35 days');
        expect(DateTimeHelper::isThisMonth($date))->toBeFalse();
    });
});

describe('interval helpers', function () {
    test('creates date intervals from seconds', function (int $expected, int $input) {
        $interval = DateTimeHelper::toDateInterval($input);

        expect(DateTimeHelper::intervalToSeconds($interval))->toBe($expected);
    })->with([
        'positive' => [10, 10],
        'negative' => [-10, -10],
    ]);

    test('returns false for zero date interval input', function () {
        expect(DateTimeHelper::toDateInterval(0))->toBeFalse();
    });

    test('converts intervals to seconds', function (int $expected, string $duration) {
        $interval = new DateInterval($duration);

        expect([
            $expected,
            $expected + 3600,
            $expected - 3600,
        ])->toContain(DateTimeHelper::intervalToSeconds($interval));
    })->with([
        'one day' => [86400, 'P1D'],
        'one day one hour' => [90000, 'P1DT1H'],
    ]);
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

describe('legacy compatibility cases retained from the old suite', function () {
    test('validates legacy interval strings', function (bool $expected, string $intervalString) {
        expect(LegacyDateTimeHelper::isValidIntervalString($intervalString))->toBe($expected);
    })->with([
        'one day' => [true, '1 day'],
        'one hour' => [true, '1 hour'],
        'compound' => [true, '1 hour + 1 day'],
        'one second' => [true, '1 second'],
        'one year' => [true, '1 year'],
        'one month' => [true, '1 month'],
        'one minutes' => [true, '1 minutes'],
    ]);

    test('creates legacy intervals from seconds', function (int $secondsResult, int $formattedResult, int $input) {
        $interval = LegacyDateTimeHelper::secondsToInterval($input);

        expect($interval->s)->toBe($secondsResult)
            ->and((int) $interval->format('%s%d%h%m'))->toBe($formattedResult);
    })->with([
        'ten seconds' => [10, 10000, 10],
        'zero seconds' => [0, 0, 0],
        'large interval' => [928172, 928172000, 928172],
    ]);
});
