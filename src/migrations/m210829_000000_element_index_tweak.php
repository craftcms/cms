<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;
use craft\helpers\MigrationHelper;

/**
 * m210829_000000_element_index_tweak migration.
 */
class m210829_000000_element_index_tweak extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        MigrationHelper::dropIndexIfExists(Table::ELEMENTS, ['archived', 'dateDeleted', 'draftId', 'revisionId'], false, $this);
        $this->createIndex(null, Table::ELEMENTS, ['archived', 'dateDeleted', 'draftId', 'revisionId', 'canonicalId']);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        MigrationHelper::dropIndexIfExists(Table::ELEMENTS, ['archived', 'dateDeleted', 'draftId', 'revisionId', 'canonicalId'], false, $this);
        $this->createIndex(null, Table::ELEMENTS, ['archived', 'dateDeleted', 'draftId', 'revisionId']);
    }
}
