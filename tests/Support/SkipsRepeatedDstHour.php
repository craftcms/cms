<?php

declare(strict_types=1);

namespace CraftCms\Cms\Tests\Support;

use DateTimeImmutable;
use Illuminate\Support\Facades\Date;

/**
 * Runs tests an hour later while the hour that repeats when daylight saving time ends is under way.
 *
 * Carbon rebuilds a mocked "now" from its wall-clock time, which picks the first of the two
 * 1 a.m. hours, so a frozen time lands an hour early. Naive datetimes read back from the
 * database, like Laravel's password reset tokens, do the same.
 */
final class SkipsRepeatedDstHour
{
    public static function register(): void
    {
        $now = Date::now();

        if ($now->dst || ! $now->copy()->subHour()->dst) {
            return;
        }

        Date::setTestNow(fn () => Date::instance(
            new DateTimeImmutable('+1 hour')->setTimezone($now->getTimezone()),
        ));
    }
}
