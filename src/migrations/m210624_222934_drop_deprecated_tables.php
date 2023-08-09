<?php

namespace craft\migrations;

use craft\db\Migration;

/**
 * m210624_222934_drop_deprecated_tables migration.
 */
class m210624_222934_drop_deprecated_tables extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $tables = [
            '{{%entrydrafterrors}}',
            '{{%entryversionerrors}}',
            '{{%entrydrafts}}',
            '{{%entryversions}}',
            '{{%projectconfignames}}',
            '{{%templatecacheelements}}',
            '{{%templatecachequeries}}',
            '{{%templatecachecriteria}}',
            '{{%templatecaches}}',
        ];

        foreach ($tables as $table) {
            if ($this->db->tableExists($table)) {
                $this->dropAllForeignKeysToTable($table);
                $this->dropTable($table);
            }
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m210624_222934_drop_deprecated_tables cannot be reverted.\n";
        return false;
    }
}
