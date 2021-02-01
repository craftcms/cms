<?php

namespace craft\migrations;

use Craft;
use craft\base\Field;
use craft\db\Migration;
use craft\db\Table;
use craft\fieldlayoutelements\AssetTitleField;
use craft\fieldlayoutelements\TitleField;
use craft\services\ProjectConfig;

/**
 * m210121_145800_asset_indexing_changes migration.
 */
class m210121_145800_asset_indexing_changes extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->truncateTable(Table::ASSETINDEXDATA);
        $this->alterColumn(Table::ASSETINDEXDATA, 'sessionId', $this->integer()->notNull());
        $this->addColumn(Table::ASSETINDEXDATA, 'isSkipped', $this->boolean()->defaultValue(false)->after('recordId'));

        $this->createTable(Table::ASSETINDEXINGSESSIONS, [
            'id' => $this->primaryKey(),
            'indexedVolumes' => $this->text(),
            'totalEntries' => $this->integer(),
            'processedEntries' => $this->integer()->notNull()->defaultValue(0),
            'cacheRemoteImages' => $this->boolean(),
            'queueId' => $this->integer(),
            'actionRequired' => $this->boolean()->defaultValue(false),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->addForeignKey(null, Table::ASSETINDEXDATA, ['sessionId'], Table::ASSETINDEXINGSESSIONS, ['id'], 'CASCADE', null);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m210121_145800_asset_indexing_changes cannot be reverted.\n";
        return false;
    }
}
