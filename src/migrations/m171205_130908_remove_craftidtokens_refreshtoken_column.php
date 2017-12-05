<?php

namespace craft\migrations;

use craft\db\Migration;

/**
 * m171205_130908_remove_craftidtokens_refreshtoken_column migration.
 */
class m171205_130908_remove_craftidtokens_refreshtoken_column extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        if (!$this->db->columnExists('{{%craftidtokens}}', 'refreshToken')) {
            // Migration has already run
            return true;
        }

        $this->dropColumn('{{%craftidtokens}}', 'refreshToken');
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m171205_130908_remove_craftidtokens_refreshtoken_column cannot be reverted.\n";
        return false;
    }
}
