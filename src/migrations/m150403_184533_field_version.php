<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;

/**
 * m150403_184533_field_version migration.
 */
class m150403_184533_field_version extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        if (!$this->db->columnExists(Table::INFO, 'fieldVersion')) {
            $this->addColumn(Table::INFO, 'fieldVersion', $this->integer()->after('maintenance')->notNull()->defaultValue(1));
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m150403_184533_field_version cannot be reverted.\n";

        return false;
    }
}
