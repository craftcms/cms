<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;

/**
 * m230801_062213_auth_identities migration.
 */
class m230801_062213_auth_identities extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $this->dropTableIfExists(Table::AUTH);

        $this->createTable(Table::AUTH, [
            'provider' => $this->string()->notNull(),
            'identityId' => $this->string()->notNull(),
            'userId' => $this->integer()->notNull(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'PRIMARY KEY([[provider]], [[identityId]], [[userId]])',
        ]);

        $this->addForeignKey(null, Table::AUTH, ['userId'], Table::USERS, ['id'], 'CASCADE', null);

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        $this->dropTableIfExists(Table::AUTH);
        return true;
    }
}
