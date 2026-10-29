<?php

declare(strict_types=1);

namespace CraftCms\Cms\GarbageCollection\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * @since 6.0.0
 */
class DeleteOrphanedForeignKeyRows extends GarbageCollectionAction
{
    public function __invoke(): void
    {
        $this->components->task(
            'deleting orphaned foreign key rows',
            function () {
                try {
                    Schema::disableForeignKeyConstraints();
                    $disabledFkChecks = true;
                } catch (Throwable) {
                    $disabledFkChecks = false;
                }

                // Schema introspection returns physical table names, but the query builder adds the prefix itself.
                $prefix = DB::getTablePrefix();

                foreach (Schema::getTables(Schema::getCurrentSchemaListing()) as $table) {
                    if (! str_starts_with($table['name'], $prefix)) {
                        continue;
                    }

                    $unprefixedName = substr($table['name'], strlen($prefix));
                    $tableName = isset($table['schema']) ? "{$table['schema']}.$unprefixedName" : $unprefixedName;

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
                        $localColumns = $foreignKey['columns'];
                        $foreignColumns = $foreignKey['foreign_columns'];

                        $children = DB::table($tableName);
                        $parent = DB::table($referencedTable.' as parent')->selectRaw('1');

                        if ($tableName === $referencedTable) {
                            // Materialize self-referencing keys so MySQL can delete from the same table.
                            $parent->fromSub(DB::table($referencedTable)->select($foreignColumns)->distinct(), 'parent');
                        }

                        foreach (array_combine($localColumns, $foreignColumns) as $localCol => $foreignCol) {
                            $children->whereNotNull("$unprefixedName.$localCol");
                            $parent->whereColumn("parent.$foreignCol", "$unprefixedName.$localCol");
                        }

                        $children->whereNotExists($parent)->delete();
                    }
                }

                if ($disabledFkChecks) {
                    Schema::enableForeignKeyConstraints();
                }
            },
        );
    }
}
