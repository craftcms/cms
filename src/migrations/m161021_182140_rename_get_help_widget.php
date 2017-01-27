<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\widgets\CraftSupport;

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
                'type' => CraftSupport::class
            ],
            [
                'type' => 'craft\widgets\GetHelp'
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
