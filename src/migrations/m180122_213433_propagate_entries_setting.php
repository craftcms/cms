<?php

namespace craft\migrations;

use craft\db\Migration;

/**
 * m180122_213433_propagate_entries_setting migration.
 */
class m180122_213433_propagate_entries_setting extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addColumn('{{%sections}}', 'propagateEntries', $this->boolean()->after('enableVersioning')->defaultValue(true)->notNull());
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m180122_213433_propagate_entries_setting cannot be reverted.\n";
        return false;
    }
}
