<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Table;
use craft\queue\jobs\ConvertEntryRevisions;

/**
 * m190312_152740_element_revisions migration.
 */
class m190312_152740_element_revisions extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        // drafts and revisions tables
        $this->createTable(Table::DRAFTS, [
            'id' => $this->primaryKey(),
            'sourceId' => $this->integer()->notNull(),
            'revisionId' => $this->integer()->notNull(),
            'creatorId' => $this->integer()->notNull(),
            'name' => $this->string()->notNull(),
            'notes' => $this->text(),
        ]);

        $this->createTable(Table::REVISIONS, [
            'id' => $this->primaryKey(),
            'sourceId' => $this->integer()->notNull(),
            'creatorId' => $this->integer()->notNull(),
            'num' => $this->integer()->notNull(),
            'notes' => $this->text(),
            'snapshot' => $this->mediumText(),
        ]);

        $this->addForeignKey(null, Table::DRAFTS, ['creatorId'], Table::USERS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::DRAFTS, ['revisionId'], Table::REVISIONS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::DRAFTS, ['sourceId'], Table::ELEMENTS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::REVISIONS, ['creatorId'], Table::USERS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::REVISIONS, ['sourceId'], Table::ELEMENTS, ['id'], 'CASCADE', null);
        $this->createIndex(null, Table::REVISIONS, ['sourceId', 'num'], true);

        // elements table
        $this->addColumn(Table::ELEMENTS, 'draftId', $this->integer()->after('id'));
        $this->addColumn(Table::ELEMENTS, 'revisionId', $this->integer()->after('draftId'));

        $this->addForeignKey(null, Table::ELEMENTS, ['draftId'], Table::DRAFTS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::ELEMENTS, ['revisionId'], Table::REVISIONS, ['id'], 'CASCADE', null);

        // add error columns to the old entry draft and version tables
        $this->addColumn(Table::ENTRYDRAFTS, 'error', $this->string(500));
        $this->addColumn(Table::ENTRYVERSIONS, 'error', $this->string(500));
        $this->createIndex(null, Table::ENTRYDRAFTS, ['error', 'id']);
        $this->createIndex(null, Table::ENTRYVERSIONS, ['error', 'id']);

        // Queue up a ConvertEntryRevisions job
        Craft::$app->getQueue()->push(new ConvertEntryRevisions());
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m190312_152740_element_revisions cannot be reverted.\n";
        return false;
    }
}
