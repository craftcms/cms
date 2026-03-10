<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;

/**
 * m240619_091352_add_auth_2fa_timestamp migration.
 */
class m240619_091352_add_auth_2fa_timestamp extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        if (!$this->db->columnExists(Table::AUTHENTICATOR, 'timestamp')) {
            $this->addColumn(
                Table::AUTHENTICATOR,
                'oldTimestamp',
                $this->integer()->unsigned()->defaultValue(null)->after('auth2faSecret')
            );
        }
        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m240619_091352_add_auth_2fa_timestamp cannot be reverted.\n";
        return false;
    }
}
