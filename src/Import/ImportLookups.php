<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import;

use Closure;
use CraftCms\Cms\Import\Importers\BaseImporter;
use WeakMap;

/**
 * Remembers the element IDs an import has looked up its items' values by, such as users by email or entries by
 * slug, for as long as the importer is in use: one preview request, or one batch of a run. Items tend to name the
 * same few elements over and over, which would otherwise each be a query or two per item.
 *
 * A value that matched nothing is only remembered while previewing, as a run may yet create what a later item names.
 *
 * @since 6.0.0
 */
final class ImportLookups
{
    /** @var WeakMap<BaseImporter, array<string, int|null>>|null */
    private static ?WeakMap $lookups = null;

    /**
     * Returns the IDs `$lookup` finds for the values, keyed by lowercased value as `$lookup` keys them, looking up only
     * those not already looked up in the same scope.
     *
     * @param  string  $scope  What else the lookup depends on, such as the field and attribute.
     * @param  list<string>  $values
     * @param  Closure(list<string>): array<string, int>  $lookup
     * @return array<string, int>
     */
    public static function remember(BaseImporter $importer, string $scope, array $values, Closure $lookup): array
    {
        self::$lookups ??= new WeakMap;
        $remembered = self::$lookups[$importer] ?? [];
        $ids = [];
        $unknown = [];

        foreach ($values as $value) {
            $key = $scope."\0".mb_strtolower($value);

            if (! array_key_exists($key, $remembered)) {
                $unknown[] = $value;
            } elseif ($remembered[$key] !== null) {
                $ids[mb_strtolower($value)] = $remembered[$key];
            }
        }

        if ($unknown !== []) {
            $found = $lookup($unknown);

            foreach ($unknown as $value) {
                $id = $found[mb_strtolower($value)] ?? null;

                if ($id !== null || $importer->previewing) {
                    $remembered[$scope."\0".mb_strtolower($value)] = $id;
                }

                if ($id !== null) {
                    $ids[mb_strtolower($value)] = $id;
                }
            }

            self::$lookups[$importer] = $remembered;
        }

        return $ids;
    }
}
