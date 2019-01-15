<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Table;

/**
 * m190104_152725_store_licensed_plugin_editions migration.
 */
class m190104_152725_store_licensed_plugin_editions extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addColumn(Table::PLUGINS, 'licensedEdition', $this->string()->after('licenseKeyStatus'));
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m190104_152725_store_licensed_plugin_editions cannot be reverted.\n";
        return false;
    }
}
