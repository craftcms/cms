<?php

namespace craft\app\migrations;

use Craft;
use craft\app\db\Migration;

/**
 * m161021_182140_rename_get_help_widget migration.
 */
class m161021_182140_rename_get_help_widget extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->update(
            '{{%widgets}}',
            [
                'type' => 'craft\app\widgets\CraftSupport'
            ],
            [
                'type' => 'craft\app\widgets\GetHelp'
            ]);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m161021_182140_rename_get_help_widget cannot be reverted.\n";
        return false;
    }
}
