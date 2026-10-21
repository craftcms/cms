<?php

declare(strict_types=1);

namespace CraftCms\Cms\Database\Connections;

use CraftCms\Cms\Support\Query;
use DateTimeInterface;
use Override;

/**
 * Binds dates in UTC, which is how every date is stored.
 *
 * Laravel formats a bound date as-is, in its own time zone, and Craft sets PHP’s
 * default time zone to the system time zone, so `now()` and the like would
 * otherwise be written, and compared, in local time.
 *
 * @since 6.0.0
 */
trait PreparesDatesInUtc
{
    /**
     * @param  array<array-key, mixed>  $bindings
     * @return array<array-key, mixed>
     */
    #[Override]
    public function prepareBindings(array $bindings): array
    {
        foreach ($bindings as $key => $value) {
            if ($value instanceof DateTimeInterface) {
                $bindings[$key] = Query::prepareDateForDb($value);
            }
        }

        return parent::prepareBindings($bindings);
    }
}
