<?php

namespace craft\migrations;

use craft\db\Migration;
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
        if (!MigrationHelper::doesForeignKeyExist('{{%taggroups}}', 'fieldLayoutId')) {
            $this->addForeignKey(null, '{{%taggroups}}', ['fieldLayoutId'], '{{%fieldlayouts}}', ['id'], 'SET NULL', null);
        }

        $this->alterColumn('{{%entryversions}}', 'notes', $this->text());
        $this->alterColumn('{{%assetindexdata}}', 'uri', $this->text());

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
