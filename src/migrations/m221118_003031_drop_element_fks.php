<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;

/**
 * m221118_003031_drop_element_fks migration.
 */
class m221118_003031_drop_element_fks extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $this->dropForeignKeyIfExists(Table::RELATIONS, ['targetId']);
        $this->dropForeignKeyIfExists(Table::STRUCTUREELEMENTS, ['elementId']);
        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        $this->addForeignKey(null, Table::RELATIONS, ['targetId'], Table::ELEMENTS, ['id'], 'CASCADE');
        $this->addForeignKey(null, Table::STRUCTUREELEMENTS, ['elementId'], Table::ELEMENTS, ['id'], 'CASCADE');
        return true;
    }
}
