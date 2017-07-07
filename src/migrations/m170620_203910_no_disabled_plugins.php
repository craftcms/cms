<?php

namespace craft\migrations;

use craft\db\Migration;

/**
 * m170620_203910_no_disabled_plugins migration.
 */
class m170620_203910_no_disabled_plugins extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->dropColumn('{{%plugins}}', 'enabled');
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m170620_203910_no_disabled_plugins cannot be reverted.\n";
        return false;
    }
}
