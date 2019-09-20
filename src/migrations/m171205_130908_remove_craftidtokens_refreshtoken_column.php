<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;

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
        if (!$this->db->columnExists(Table::CRAFTIDTOKENS, 'refreshToken')) {
            // Migration has already run
            return;
        }

        $this->dropColumn(Table::CRAFTIDTOKENS, 'refreshToken');
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
