<?php

declare(strict_types=1);

namespace CraftCms\Cms\GarbageCollection\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

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

                foreach (Schema::getTables(Schema::getCurrentSchemaListing()) as $table) {
                    $tableName = $table['schema_qualified_name'];

                    foreach (Schema::getForeignKeys($tableName) as $foreignKey) {
                        if (strtoupper((string) $foreignKey['on_delete']) !== 'CASCADE') {
                            continue;
                        }

                        $referencedTable = isset($foreignKey['foreign_schema'])
                            ? $foreignKey['foreign_schema'].'.'.$foreignKey['foreign_table']
                            : $foreignKey['foreign_table'];
                        $localColumns = $foreignKey['columns'];
                        $foreignColumns = $foreignKey['foreign_columns'];

                        $children = DB::table($tableName);
                        $parent = DB::table($referencedTable.' as parent')->selectRaw('1');

                        if ($tableName === $referencedTable) {
                            // Materialize self-referencing keys so MySQL can delete from the same table.
                            $parent->fromSub(DB::table($referencedTable)->select($foreignColumns)->distinct(), 'parent');
                        }

                        foreach (array_combine($localColumns, $foreignColumns) as $localCol => $foreignCol) {
                            $children->whereNotNull("$tableName.$localCol");
                            $parent->whereColumn("parent.$foreignCol", "$tableName.$localCol");
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
