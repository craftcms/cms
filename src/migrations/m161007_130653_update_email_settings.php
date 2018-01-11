<?php

namespace craft\migrations;

use craft\db\Migration;

/**
 * m161007_130653_update_email_settings migration.
 */
class m161007_130653_update_email_settings extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->replace('{{%systemsettings}}', 'settings', 'daptor', 'dapter', ['category' => 'email']);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m161007_130653_update_email_settings cannot be reverted.\n";

        return false;
    }
}
