<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;
use craft\helpers\MigrationHelper;

/**
 * m170809_223337_oauth_tokens_table migration.
 */
class m170809_223338_oauth_tokens_table extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        if ($this->db->tableExists('{{%oauthtokens}}')) {
            return;
        }

        if ($this->db->tableExists('{{%oauth_tokens}}')) {
            MigrationHelper::renameTable('{{%oauth_tokens}}', '{{%oauthtokens}}');
            return;
        }

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
        echo "m170809_223338_oauth_tokens_table cannot be reverted.\n";
        return false;
    }
}
