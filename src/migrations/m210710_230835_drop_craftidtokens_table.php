<?php

namespace craft\migrations;

use craft\db\Migration;

/**
 * m210710_230835_drop_craftidtokens_table migration.
 */
class m210710_230835_drop_craftidtokens_table extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->dropTableIfExists('{{%craftidtokens}}');
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m210710_230835_drop_craftidtokens_table cannot be reverted.\n";
        return false;
    }
}
