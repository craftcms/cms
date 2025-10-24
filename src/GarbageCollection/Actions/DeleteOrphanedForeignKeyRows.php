<?php

declare(strict_types=1);

namespace CraftCms\Cms\GarbageCollection\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

final class DeleteOrphanedForeignKeyRows extends GarbageCollectionAction
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

                foreach (Schema::getTables() as $table) {
                    $tableName = $table['name'];

                    foreach (Schema::getForeignKeys($tableName) as $foreignKey) {
                        if (strtoupper($foreignKey['on_delete']) !== 'CASCADE') {
                            continue;
                        }

                        $referencedTable = $foreignKey['foreign_table'];
                        $localColumns = $foreignKey['columns'];
                        $foreignColumns = $foreignKey['foreign_columns'];

                        foreach (array_combine($localColumns, $foreignColumns) as $localCol => $foreignCol) {
                            // Build a subquery for missing parents
                            $orphans = DB::table($tableName.' as child')
                                ->leftJoin($referencedTable.' as parent', "child.$localCol", '=', "parent.$foreignCol")
                                ->whereNotNull("child.$localCol")
                                ->whereNull("parent.$foreignCol")
                                ->pluck("child.$localCol");

                            DB::table($tableName)
                                ->whereIn('id', $orphans)
                                ->delete();
                        }
                    }
                }

                if ($disabledFkChecks) {
                    Schema::enableForeignKeyConstraints();
                }
            },
        );
    }
}
