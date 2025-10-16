<?php

namespace CraftCms\Cms\GarbageCollection\Actions;

use craft\db\TableSchema;
use Illuminate\Support\Facades\Schema;
use Throwable;

final class DeleteOrphanedForeignKeyRows extends GarbageCollectionAction
{
    public function run(): void
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

                $db = \Craft::$app->getDb();

                $isMysql = $db->getIsMysql();

                foreach ($db->getSchema()->getTableSchemas() as $table) {
                    /** @var TableSchema $table */
                    $extendedFkInfo = $table->getExtendedForeignKeys();
                    $counter = 0;

                    foreach ($table->foreignKeys as $fk) {
                        if ($extendedFkInfo[$counter]['deleteType'] !== 'CASCADE') {
                            continue;
                        }

                        $fk = array_merge($fk);
                        $refTable = array_shift($fk);

                        foreach ($fk as $fkColumn => $pkColumn) {
                            if ($isMysql) {
                                $sql = <<<SQL
                                DELETE t.* FROM $table->name t
                                LEFT JOIN $refTable t2 ON t2.$pkColumn = t.$fkColumn
                                WHERE t.$fkColumn IS NOT NULL
                                AND t2.$pkColumn IS NULL
                                SQL;
                            } else {
                                $sql = <<<SQL
                                DELETE FROM $table->name t
                                WHERE t."$fkColumn" IS NOT NULL
                                AND NOT EXISTS (
                                    SELECT * FROM $refTable
                                    WHERE "$pkColumn" = t."$fkColumn"
                                )
                                SQL;
                            }

                            $db->createCommand($sql)->execute();
                        }

                        $counter++;
                    }
                }

                if ($disabledFkChecks) {
                    Schema::enableForeignKeyConstraints();
                }
            },
        );
    }
}
