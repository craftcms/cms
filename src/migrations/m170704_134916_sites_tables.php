<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\helpers\MigrationHelper;

/**
 * m170704_134916_sites_tables migration.
 */
class m170704_134916_sites_tables extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $tables = ['categorygroups', 'elements', 'sections'];

        foreach ($tables as $table) {
            $oldName = '{{%'.$table.'_i18n}}';
            $newName = '{{%'.$table.'_sites}}';

            // In case this was run in a previous update attempt
            $this->dropTableIfExists($newName);

            MigrationHelper::renameTable($oldName, $newName, $this);
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m170704_134916_sites_tables cannot be reverted.\n";
        return false;
    }
}
