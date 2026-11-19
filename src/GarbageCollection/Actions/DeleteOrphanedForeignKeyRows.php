<?php

declare(strict_types=1);

namespace CraftCms\Cms\GarbageCollection\Actions;

use Illuminate\Database\Query\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * @since 6.0.0
 */
class DeleteOrphanedForeignKeyRows extends GarbageCollectionAction
{
    private const int BATCH_SIZE = 1000;

    public function __invoke(): void
    {
        $this->components->task(
            'deleting orphaned foreign key rows',
            function () {
                // Find the orphaned rows a page at a time with plain SELECTs, which don't lock any rows,
                // and delete each page in its own transaction.
                // Foreign key checks stay enabled, so the deletions cascade and set columns to NULL as the schema says,
                // and other connections' writes only wait on the rows being deleted.
                // (https://github.com/craftcms/cms/issues/20885)

                // Schema introspection returns physical table names, but the query builder adds the prefix itself.
                $prefix = DB::getTablePrefix();

                foreach (Schema::getTables(Schema::getCurrentSchemaListing()) as $table) {
                    if (! str_starts_with($table['name'], $prefix)) {
                        continue;
                    }

                    $unprefixedName = substr($table['name'], strlen($prefix));
                    $tableName = isset($table['schema']) ? "{$table['schema']}.$unprefixedName" : $unprefixedName;
                    $primaryKey = null;

                    foreach (Schema::getForeignKeys($tableName) as $foreignKey) {
                        if (
                            strtoupper((string) $foreignKey['on_delete']) !== 'CASCADE' ||
                            ! str_starts_with($foreignKey['foreign_table'], $prefix)
                        ) {
                            continue;
                        }

                        $referencedTable = substr($foreignKey['foreign_table'], strlen($prefix));

                        if (isset($foreignKey['foreign_schema'])) {
                            $referencedTable = "{$foreignKey['foreign_schema']}.$referencedTable";
                        }

                        $primaryKey ??= collect(Schema::getIndexes($tableName))->firstWhere('primary', true)['columns'] ?? [];

                        $this->deleteOrphanedRows(
                            $tableName,
                            $referencedTable,
                            array_combine($foreignKey['columns'], $foreignKey['foreign_columns']),
                            $primaryKey,
                        );
                    }
                }
            },
        );
    }

    /**
     * @param  array<string, string>  $columns  The foreign key's columns, mapped to the referenced columns.
     * @param  string[]  $primaryKey
     */
    private function deleteOrphanedRows(string $table, string $referencedTable, array $columns, array $primaryKey): void
    {
        // Identify the rows by their primary key, so each batch deletes at most BATCH_SIZE rows
        // (tables without one fall back to the foreign key values, which may match more rows).
        // Including the foreign key values means a row whose key changed since the SELECT won't be deleted.
        $keyColumns = array_values(array_unique([...$primaryKey, ...array_keys($columns)]));
        $qualifiedKeyColumns = array_map(fn (string $column) => "child.$column", $keyColumns);

        $query = DB::table("$table as child")
            ->select($qualifiedKeyColumns)
            ->distinct()
            ->whereNotExists(function (Builder $parent) use ($referencedTable, $columns) {
                $parent->selectRaw('1')->from("$referencedTable as parent");

                foreach ($columns as $column => $referencedColumn) {
                    $parent->whereColumn("parent.$referencedColumn", "child.$column");
                }
            })
            ->limit(self::BATCH_SIZE);

        foreach ($qualifiedKeyColumns as $column) {
            $query->orderBy($column);
        }

        foreach (array_keys($columns) as $column) {
            $query->whereNotNull("child.$column");
        }

        $lastKey = null;

        do {
            $keys = (clone $query)
                // Continue after the last page, skipping any rows that couldn't be deleted
                ->when($lastKey !== null, fn (Builder $query) => $query->whereRowValues($qualifiedKeyColumns, '>', $lastKey))
                ->get()
                ->map(fn (object $row) => (array) $row)
                ->all();

            if ($keys === []) {
                break;
            }

            try {
                DB::transaction(fn () => DB::table($table)
                    ->where(function (Builder $query) use ($keys) {
                        foreach ($keys as $key) {
                            $query->orWhere(fn (Builder $query) => $query->where($key));
                        }
                    })
                    ->delete());
            } catch (QueryException $e) {
                // Most likely another foreign key restricts the deletion
                Log::warning("Couldn't delete orphaned rows from $table: {$e->getMessage()}", [__METHOD__]);
            }

            $lastKey = array_values(end($keys));
        } while (count($keys) === self::BATCH_SIZE);
    }
}
