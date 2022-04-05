<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Table;

/**
 * m210613_184103_announcements migration.
 */
class m210613_184103_announcements extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->dropTableIfExists(Table::ANNOUNCEMENTS);
        $this->createTable(Table::ANNOUNCEMENTS, [
            'id' => $this->primaryKey(),
            'userId' => $this->integer()->notNull(),
            'pluginId' => $this->integer(),
            'heading' => $this->string()->notNull(),
            'body' => $this->text()->notNull(),
            'unread' => $this->boolean()->defaultValue(true)->notNull(),
            'dateRead' => $this->dateTime(),
            'dateCreated' => $this->dateTime()->notNull(),
        ]);
        $this->createIndex(null, Table::ANNOUNCEMENTS, ['userId', 'unread', 'dateRead', 'dateCreated'], false);
        $this->createIndex(null, Table::ANNOUNCEMENTS, ['dateRead'], false);
        $this->addForeignKey(null, Table::ANNOUNCEMENTS, ['userId'], Table::USERS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::ANNOUNCEMENTS, ['pluginId'], Table::PLUGINS, ['id'], 'CASCADE', null);

        Craft::$app->getAnnouncements()->push(
            function(string $language) {
                return Craft::t('app', 'Editor Slideouts', [], $language);
            },
            function(string $language) {
                return Craft::t('app', 'Double-click entries and other editable elements to try the new editor slideout interface.', [], $language);
            }
        );

        Craft::$app->getAnnouncements()->push(
            function(string $language) {
                return Craft::t('app', 'Streamlined Entry Publishing Flow', [], $language);
            },
            function(string $language) {
                return Craft::t('app', 'The entry publishing workflow is now [simpler and more intuitive]({url}).', [
                    'url' => 'https://craftcms.com/knowledge-base/editing-entries',
                ], $language);
            }
        );
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->dropTable(Table::ANNOUNCEMENTS);
    }
}
