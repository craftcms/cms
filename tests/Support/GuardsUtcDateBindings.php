<?php

declare(strict_types=1);

namespace CraftCms\Cms\Tests\Support;

use DateTimeInterface;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Fails a test when Craft binds a date to a query in a time zone other than UTC.
 *
 * Dates are stored in UTC, as in Craft 5, so Craft passes them through
 * `Query::prepareDateForDb()` first. Laravel would store a bound `Carbon` in its
 * own time zone, which is the system time zone, not UTC. Only queries Craft runs
 * itself are checked, so Laravel's own tables, like password reset tokens, keep
 * Laravel's behavior.
 */
final class GuardsUtcDateBindings
{
    public static function register(): void
    {
        DB::beforeExecuting(function (string $sql, array $bindings): void {
            foreach ($bindings as $binding) {
                if ($binding instanceof DateTimeInterface && $binding->getOffset() !== 0 && ($caller = self::craftCaller()) !== null) {
                    throw new RuntimeException("$caller binds a date in its own time zone ({$binding->format(DATE_ATOM)}). Pass it through Query::prepareDateForDb() so it's stored in UTC.");
                }
            }
        });
    }

    /**
     * The `src/` file and line that called into Laravel's database layer, if Craft code did.
     *
     * A frame's file and line are where its function was called from, so the call into the
     * database layer is the innermost database frame called from outside the database package.
     */
    private static function craftCaller(): ?string
    {
        foreach (debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS) as $frame) {
            $file = str_replace('\\', '/', $frame['file'] ?? '');

            if (! str_starts_with($frame['class'] ?? '', 'Illuminate\\Database\\') || str_contains($file, '/Illuminate/Database/')) {
                continue;
            }

            return str_contains($file, '/src/') && ! str_contains($file, '/vendor/')
                ? preg_replace('#^.*/(src/)#', '$1', $file).':'.($frame['line'] ?? 0)
                : null;
        }

        return null;
    }
}
