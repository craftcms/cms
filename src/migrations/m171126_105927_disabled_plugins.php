<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;

/**
 * m171126_105927_disabled_plugins migration.
 */
class m171126_105927_disabled_plugins extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        if (!$this->db->columnExists(Table::PLUGINS, 'enabled')) {
            $this->addColumn(Table::PLUGINS, 'enabled', $this->boolean()->after('licenseKeyStatus')->defaultValue(false)->notNull());
            $this->update(Table::PLUGINS, ['enabled' => true]);
        }

        $this->createIndex(null, Table::PLUGINS, ['enabled']);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m171126_105927_disabled_plugins cannot be reverted.\n";
        return false;
    }
}
