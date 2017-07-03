<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;

/**
 * m170703_181539_plugins_table_tweaks migration.
 */
class m170703_181539_plugins_table_tweaks extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        // Remove lengths
        $this->alterColumn('{{%plugins}}', 'handle', $this->string()->notNull());
        $this->alterColumn('{{%plugins}}', 'version', $this->string()->notNull());
        $this->alterColumn('{{%plugins}}', 'schemaVersion', $this->string()->notNull());
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m170703_181539_plugins_table_tweaks cannot be reverted.\n";
        return false;
    }
}
