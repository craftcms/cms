<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;

/**
 * m230503_120303_add_recoverycodes_table migration.
 */
class m230503_120303_add_recoverycodes_table extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $this->dropTableIfExists(Table::RECOVERYCODES);

        $this->createTable(Table::RECOVERYCODES, [
            'id' => $this->primaryKey(),
            'userId' => $this->integer()->notNull(),
            'recoveryCodes' => $this->text()->defaultValue(null),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
        ]);

        $this->addForeignKey(null, Table::RECOVERYCODES, ['userId'], Table::USERS, ['id'], 'CASCADE', null);

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        $this->dropTableIfExists(Table::RECOVERYCODES);
        return true;
    }
}
