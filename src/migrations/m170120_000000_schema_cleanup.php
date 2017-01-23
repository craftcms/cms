<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;
use craft\helpers\MigrationHelper;

/**
 * m170120_000000_schema_cleanup migration.
 */
class m170120_000000_schema_cleanup extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $tablePrefix = Craft::$app->getConfig()->getDbTablePrefix();

        Craft::$app->getDb()->createCommand("ALTER TABLE `{$tablePrefix}assets` MODIFY COLUMN `id` INT(11) AUTO_INCREMENT");
        Craft::$app->getDb()->createCommand("ALTER TABLE `{$tablePrefix}entries` MODIFY COLUMN `id` INT(11) AUTO_INCREMENT");
        Craft::$app->getDb()->createCommand("ALTER TABLE `{$tablePrefix}categories` MODIFY COLUMN `id` INT(11) AUTO_INCREMENT");
        Craft::$app->getDb()->createCommand("ALTER TABLE `{$tablePrefix}matrixblocks` MODIFY COLUMN `id` INT(11) AUTO_INCREMENT");
        Craft::$app->getDb()->createCommand("ALTER TABLE `{$tablePrefix}tags` MODIFY COLUMN `id` INT(11) AUTO_INCREMENT");
        Craft::$app->getDb()->createCommand("ALTER TABLE `{$tablePrefix}users` MODIFY COLUMN `id` INT(11) AUTO_INCREMENT");

        if (!MigrationHelper::doesForeignKeyExist('{{%taggroups}}', 'fieldLayoutId')) {
            $this->addForeignKey($this->db->getForeignKeyName('{{%taggroups}}', 'fieldLayoutId'), '{{%taggroups}}', 'fieldLayoutId', '{{%fieldLayouts}}', 'id', 'SET NULL', null);
        }

        $this->alterColumn('{{%entryversions}}', 'notes', $this->text());
        $this->alterColumn('{{%assetindexdata}}', 'uri', $this->text());

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m170120_000000_schema_cleanup cannot be reverted.\n";
        return false;
    }
}
