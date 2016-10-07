<?php

namespace craft\app\migrations;

use Craft;
use craft\app\db\Migration;

/**
 * m161007_130653_delete_mailer_settings migration.
 */
class m161007_130653_delete_mailer_settings extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->delete('{{%systemsettings}}', ['category' => 'mailer']);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m161007_130653_delete_mailer_settings cannot be reverted.\n";
        return false;
    }
}
