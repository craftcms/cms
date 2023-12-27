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
        if ($this->db->getIsMysql()) {
            $this->dropPrimaryKey('elementId', Table::SEARCHINDEX);
            $this->addColumn(Table::SEARCHINDEX, 'layoutElementUid', $this->uid()->after('fieldId'));
            $this->addPrimaryKey('layoutUid', Table::SEARCHINDEX, ['elementId', 'attribute', 'fieldId', 'layoutElementUid', 'siteId']);
        } else {
            $pkName = $this->db->getSchema()->getRawTableName(Table::SEARCHINDEX) . '_pkey';

            $this->dropPrimaryKey($pkName, Table::SEARCHINDEX);
            $this->addColumn(Table::SEARCHINDEX, 'layoutElementUid', $this->uid()->after('fieldId'));
            $this->addPrimaryKey($pkName, Table::SEARCHINDEX, ['elementId', 'attribute', 'fieldId', 'layoutElementUid', 'siteId']);
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m231205_081215_searchindex_amend_pk cannot be reverted.\n";
        return false;
    }
}
