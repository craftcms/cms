<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;

/**
 * m240906_115231_add_addresses_fieldid_fk_constraint migration.
 */
class m240906_115231_add_addresses_fieldid_fk_constraint extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        // first clean up addresses that already belong to fields that were hard deleted
        $addressesTable = Table::ADDRESSES;
        $fieldsTable = Table::FIELDS;

        if ($this->db->getIsMysql()) {
            $sql = <<<SQL
DELETE [[a]].* FROM $addressesTable [[a]]
LEFT JOIN $fieldsTable [[f]] ON [[f.id]] = [[a.fieldId]]
WHERE [[a.fieldId]] IS NOT NULL AND 
    [[f.id]] IS NULL
SQL;
        } else {
            $sql = <<<SQL
DELETE FROM $addressesTable
USING $addressesTable [[a]]
LEFT JOIN $fieldsTable [[f]] ON [[f.id]] = [[a.fieldId]]
WHERE
    $addressesTable.[[id]] = [[a.id]] AND 
    [[a.fieldId]] IS NOT NULL AND 
    [[f.id]] IS NULL
SQL;
        }

        $this->db->createCommand($sql)->execute();

        // and now add a foreign key
        $this->addForeignKey(null, Table::ADDRESSES, ['fieldId'], Table::FIELDS, ['id'], 'CASCADE', null);

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m240906_115231_add_addresses_fieldid_fk_constraint cannot be reverted.\n";
        return false;
    }
}
