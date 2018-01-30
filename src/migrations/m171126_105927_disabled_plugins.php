<?php

namespace craft\migrations;

use craft\db\Migration;

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
        if (!$this->db->columnExists('{{%plugins}}', 'enabled')) {
            $this->addColumn('{{%plugins}}', 'enabled', $this->boolean()->after('licenseKeyStatus')->defaultValue(false)->notNull());
            $this->update('{{%plugins}}', ['enabled' => true]);
        }

        $this->createIndex(null, '{{%plugins}}', ['enabled']);
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
