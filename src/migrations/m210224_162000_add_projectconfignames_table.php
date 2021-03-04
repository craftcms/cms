<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Table;

/**
 * m210224_162000_add_projectconfignames_table migration.
 */
class m210224_162000_add_projectconfignames_table extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->createTable(Table::PROJECTCONFIGNAMES, [
            'uid' => $this->uid()->notNull(),
            'name' => $this->string()->notNull(),
            'PRIMARY KEY([[uid]])',
        ]);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m210224_162000_add_projectconfignames_table cannot be reverted.\n";
        return false;
    }
}
