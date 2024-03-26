<?php

namespace craft\migrations;

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
        $this->dropTableIfExists(Table::ENTRIES_AUTHORS);

        $this->createTable(Table::ENTRIES_AUTHORS, [
            'entryId' => $this->integer()->notNull(),
            'authorId' => $this->integer()->notNull(),
            'sortOrder' => $this->smallInteger()->unsigned()->notNull(),
            'PRIMARY KEY([[entryId]], [[authorId]])',
        ]);

        $this->createIndex(null, Table::ENTRIES_AUTHORS, ['authorId'], false);
        $this->createIndex(null, Table::ENTRIES_AUTHORS, ['entryId', 'sortOrder'], false);

        $this->addForeignKey(null, Table::ENTRIES_AUTHORS, ['entryId'], Table::ENTRIES, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::ENTRIES_AUTHORS, ['authorId'], Table::USERS, ['id'], 'CASCADE', null);

        // populate the table
        $this->execute(sprintf(<<<SQL
INSERT INTO %s ([[entryId]], [[authorId]], [[sortOrder]])
SELECT [[id]], [[authorId]], '1' FROM %s
WHERE [[authorId]] IS NOT NULL
SQL, Table::ENTRIES_AUTHORS, Table::ENTRIES));

        // remove authorId column from entries table
        $this->dropForeignKeyIfExists(Table::ENTRIES, 'authorId');
        $this->dropIndexIfExists(Table::ENTRIES, 'authorId');
        $this->dropColumn(Table::ENTRIES, 'authorId');

        // update the changedattributes table
        $this->update(Table::CHANGEDATTRIBUTES, ['attribute' => 'authorIds'], ['attribute' => 'authorId'], updateTimestamp: false);

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
