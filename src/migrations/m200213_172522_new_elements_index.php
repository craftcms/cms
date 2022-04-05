<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;
use craft\helpers\MigrationHelper;

/**
 * m200213_172522_new_elements_index migration.
 */
class m200213_172522_new_elements_index extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->createIndex(null, Table::ELEMENTS, ['archived', 'dateDeleted', 'draftId', 'revisionId']);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        MigrationHelper::dropIndexIfExists(Table::ELEMENTS, ['archived', 'dateDeleted', 'draftId', 'revisionId'], false, $this);
    }
}
