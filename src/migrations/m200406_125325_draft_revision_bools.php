<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Table;
use craft\helpers\MigrationHelper;

/**
 * m200406_125325_draft_revision_bools migration.
 */
class m200406_125325_draft_revision_bools extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addColumn(Table::ELEMENTS, 'isSource', $this->boolean()->notNull()->defaultValue(true)->after('type'));

        MigrationHelper::dropIndexIfExists(Table::ELEMENTS, ['archived', 'dateCreated'], false);
        MigrationHelper::dropIndexIfExists(Table::ELEMENTS, ['archived', 'dateDeleted', 'draftId', 'revisionId'], false);

        $this->createIndex(null, Table::ELEMENTS, ['archived', 'dateDeleted', 'draftId'], false);
        $this->createIndex(null, Table::ELEMENTS, ['archived', 'dateCreated', 'revisionId'], false);
        $this->createIndex(null, Table::ELEMENTS, ['archived', 'dateDeleted', 'isSource'], false);

        $this->update(Table::ELEMENTS, ['isSource' => false], [
            'or',
            ['not', ['draftId' => null]],
            ['not', ['revisionId' => null]],
        ]);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m200406_125325_draft_revision_bools cannot be reverted.\n";
        return false;
    }
}
