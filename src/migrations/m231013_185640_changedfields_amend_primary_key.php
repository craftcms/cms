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
            // We need to do this as one command, to avoid a GIPK error
            // https://www.mydbops.com/blog/generate-invisible-primary-key-gipk-mysql-80/
            // https://github.com/craftcms/cms/issues/15853
            $this->execute(sprintf(<<<SQL
ALTER TABLE %s DROP PRIMARY KEY,
ADD CONSTRAINT `%s` PRIMARY KEY (`elementId`, `siteId`, `fieldId`, `layoutElementUid`);
SQL, Table::CHANGEDFIELDS, $this->db->getPrimaryKeyName()));
        } else {
            $pkName = $this->db->getSchema()->getTablePrimaryKey(Table::CHANGEDFIELDS)->name;

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
