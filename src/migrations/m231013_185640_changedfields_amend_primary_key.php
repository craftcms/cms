<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;

/**
 * m231013_185640_changedfields_amend_primary_key migration.
 */
class m231013_185640_changedfields_amend_primary_key extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        if ($this->db->getIsMysql()) {
            $this->dropPrimaryKey('elementId', Table::CHANGEDFIELDS);
            $this->addPrimaryKey('layoutUid', Table::CHANGEDFIELDS, ['elementId', 'siteId', 'fieldId', 'layoutElementUid']);
        } else {
            $pkName = $this->db->getSchema()->getRawTableName(Table::CHANGEDFIELDS) . '_pkey';

            $this->dropPrimaryKey($pkName, Table::CHANGEDFIELDS);
            $this->addPrimaryKey($pkName, Table::CHANGEDFIELDS, ['elementId', 'siteId', 'fieldId', 'layoutElementUid']);
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m231013_185640_changedfields_amend_primary_key cannot be reverted.\n";
        return false;
    }
}
