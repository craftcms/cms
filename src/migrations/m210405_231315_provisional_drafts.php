<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;
use craft\helpers\MigrationHelper;

/**
 * m210405_231315_provisional_drafts migration.
 */
class m210405_231315_provisional_drafts extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addColumn(Table::DRAFTS, 'provisional', $this->boolean()->notNull()->defaultValue(false)->after('creatorId'));
        $this->createIndex(null, Table::DRAFTS, ['creatorId', 'provisional'], false);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        MigrationHelper::dropIndexIfExists(Table::DRAFTS, ['creatorId', 'provisional'], false, $this);
        $this->dropColumn(Table::DRAFTS, 'provisional');
    }
}
