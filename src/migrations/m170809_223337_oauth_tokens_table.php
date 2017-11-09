<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;

/**
 * m170809_223337_oauth_tokens_table migration.
 */
class m170809_223337_oauth_tokens_table extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->createTable('{{%oauthtokens}}', [
            'id' => $this->primaryKey(),
            'userId' => $this->integer()->notNull(),
            'provider' => $this->string()->notNull(),
            'accessToken' => $this->text()->notNull(),
            'tokenType' => $this->string(),
            'expiresIn' => $this->integer(),
            'expiryDate' => $this->dateTime(),
            'refreshToken' => $this->text(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->createIndex(null, '{{%oauthtokens}}', ['provider', 'userId'], false);
        $this->addForeignKey(null, '{{%oauthtokens}}', ['userId'], '{{%users}}', ['id'], 'CASCADE', null);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m170809_223337_oauth_tokens_table cannot be reverted.\n";
        return false;
    }
}
