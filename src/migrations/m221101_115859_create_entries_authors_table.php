<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Table;

/**
 * m221101_115859_create_entries_authors_table migration.
 */
class m221101_115859_create_entries_authors_table extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $this->dropTableIfExists(Table::ADDRESSES);

        $this->createTable(Table::ENTRIES_AUTHORS, [
            'id' => $this->primaryKey(),
            'elementId' => $this->integer()->notNull(),
            'authorId' => $this->integer(),
            'sortOrder' => $this->smallInteger()->unsigned(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->addForeignKey(null, Table::ENTRIES_AUTHORS, ['elementId'], Table::ELEMENTS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::ENTRIES_AUTHORS, ['authorId'], Table::USERS, ['id'], 'SET NULL', null);

        // TODO: add data migration
        // TODO: remove authorId column from entries table

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m221101_115859_create_entries_authors_table cannot be reverted.\n";
        return false;
    }
}
