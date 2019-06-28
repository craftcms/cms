<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Table;
use craft\helpers\MigrationHelper;

/**
 * m190627_100505_user_urls_and_multi_site migration.
 */
class m190627_100505_user_urls_and_multi_site extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->createTable('{{%users_sites}}', [
            'id' => $this->primaryKey(),
            'siteId' => $this->integer()->notNull(),
            'hasUrls' => $this->boolean()->defaultValue(true)->notNull(),
            'uriFormat' => $this->text(),
            'template' => $this->string(500),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->addForeignKey(
            null,
            '{{%users_sites}}',
            ['siteId'],
            '{{%sites}}',
            ['id'],
            'CASCADE',
            null
        );

        // Just make sure our shiny new config setting is set to false.
        Craft::$app->getProjectConfig()->set('users.enableRoutingAndMultisite', false);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m190627_100505_user_urls_and_multi_site cannot be reverted.\n";
        return false;
    }
}
