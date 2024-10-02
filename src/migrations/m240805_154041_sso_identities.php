<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;

/**
 * m240805_154041_sso_identities migration.
 */
class m240805_154041_sso_identities extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $this->dropTableIfExists(Table::SSO_IDENTITIES);

        $this->createTable(Table::SSO_IDENTITIES, [
            'provider' => $this->string()->notNull(),
            'identityId' => $this->string()->notNull(),
            'userId' => $this->integer()->notNull(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'PRIMARY KEY([[provider]], [[identityId]], [[userId]])',
        ]);

        $this->addForeignKey(null, Table::SSO_IDENTITIES, ['userId'], Table::USERS, ['id'], 'CASCADE', null);

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        $this->dropTableIfExists(Table::SSO_IDENTITIES);
        return true;
    }
}
