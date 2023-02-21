<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;

/**
 * m230221_185926_drop_element_fks migration.
 */
class m230221_185926_drop_element_fks extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->dropForeignKeyIfExists(Table::RELATIONS, ['targetId']);
        $this->dropForeignKeyIfExists(Table::STRUCTUREELEMENTS, ['elementId']);
        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->addForeignKey(null, Table::RELATIONS, ['targetId'], Table::ELEMENTS, ['id'], 'CASCADE');
        $this->addForeignKey(null, Table::STRUCTUREELEMENTS, ['elementId'], Table::ELEMENTS, ['id'], 'CASCADE');
        return true;
    }
}
