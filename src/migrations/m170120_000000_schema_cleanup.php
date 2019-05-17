<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;
use craft\helpers\MigrationHelper;

/**
 * m170120_000000_schema_cleanup migration.
 */
class m170120_000000_schema_cleanup extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        if (!MigrationHelper::doesForeignKeyExist(Table::TAGGROUPS, 'fieldLayoutId')) {
            $this->addForeignKey(null, Table::TAGGROUPS, ['fieldLayoutId'], Table::FIELDLAYOUTS, ['id'], 'SET NULL', null);
        }

        $this->alterColumn(Table::ENTRYVERSIONS, 'notes', $this->text());
        $this->alterColumn(Table::ASSETINDEXDATA, 'uri', $this->text());

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m170120_000000_schema_cleanup cannot be reverted.\n";

        return false;
    }
}
