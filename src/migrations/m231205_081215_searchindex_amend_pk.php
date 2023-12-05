<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;

/**
 * m231205_081215_searchindex_amend_pk migration.
 */
class m231205_081215_searchindex_amend_pk extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $this->dropPrimaryKey('elementId', Table::SEARCHINDEX);

        $this->addColumn(Table::SEARCHINDEX, 'layoutElementUid', $this->uid()->after('fieldId'));

        $this->addPrimaryKey('layoutUid', Table::SEARCHINDEX, ['elementId', 'attribute', 'fieldId', 'layoutElementUid', 'siteId']);

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        $this->dropPrimaryKey('layoutUid', Table::SEARCHINDEX);

        $this->dropColumn(Table::SEARCHINDEX, 'layoutElementUid');

        $this->addPrimaryKey('elementId', Table::SEARCHINDEX, ['elementId', 'attribute', 'fieldId', 'siteId']);

        return true;
    }
}
