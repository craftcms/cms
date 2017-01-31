<?php

namespace craft\migrations;

use craft\db\Migration;

/**
 * m161025_000000_fix_char_columns migration.
 */
class m161025_000000_fix_char_columns extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->alterColumn(
            '{{%users}}',
            'password',
            $this->string()
        );

        $this->alterColumn(
            '{{%users}}',
            'verificationCode',
            $this->string()
        );
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
