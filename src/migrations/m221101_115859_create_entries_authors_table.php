<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Table;
use craft\helpers\Db;
use craft\records\Entry as EntryRecord;

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

        // add data migration
        $entries = EntryRecord::find()
            ->select(['id', 'authorId'])
            ->where('authorId IS NOT NULL')
            ->orderBy('id ASC')
            ->all();
        $entriesAuthors = [];
        if (!empty($entries)) {
            foreach ($entries as $entry) {
                $entriesAuthors[] = [$entry['id'], $entry['authorId'], '1'];
            }

            if (!empty($entriesAuthors)) {
                $db = Craft::$app->getDb();
                Db::batchInsert(Table::ENTRIES_AUTHORS, ['elementId', 'authorId', 'sortOrder'], $entriesAuthors, $db);
            }
        }

        // remove authorId column from entries table
        $this->dropForeignKeyIfExists(Table::ENTRIES, 'authorId');
        $this->dropIndexIfExists(Table::ENTRIES, 'authorId');
        $this->dropColumn(Table::ENTRIES, 'authorId');

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
