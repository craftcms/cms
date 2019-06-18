<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Table;
use craft\helpers\MigrationHelper;

/**
 * m190617_164400_add_gql_token_table migration.
 */
class m190617_164400_add_gql_token_table extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->createTable(Table::GQLTOKENS, [
            'id' => $this->primaryKey(),
            'name' => $this->string()->notNull(),
            'accessToken' => $this->string()->notNull(),
            'enabled' => $this->boolean()->notNull()->defaultValue(true),
            'expiryDate' => $this->dateTime(),
            'lastUsed' => $this->dateTime(),
            'permissions' => $this->text(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->createIndex(null, Table::GQLTOKENS, ['accessToken'], true);
        $this->createIndex(null, Table::GQLTOKENS, ['name'], true);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m190617_164400_add_gql_token_table cannot be reverted.\n";
        return false;
    }
}
