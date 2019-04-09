<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;

/**
 * m180416_205628_resourcepaths_table migration.
 */
class m180416_205628_resourcepaths_table extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        // In case this was run in a previous update attempt
        $this->dropTableIfExists(Table::RESOURCEPATHS);

        $this->createTable(Table::RESOURCEPATHS, [
            'hash' => $this->string()->notNull(),
            'path' => $this->string()->notNull(),
            'PRIMARY KEY([[hash]])',
        ]);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m180416_205628_resourcepaths_table cannot be reverted.\n";
        return false;
    }
}
