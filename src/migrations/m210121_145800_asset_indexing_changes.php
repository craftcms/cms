<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;

/**
 * m210121_145800_asset_indexing_changes migration.
 */
class m210121_145800_asset_indexing_changes extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $this->truncateTable(Table::ASSETINDEXDATA);
        $this->dropColumn(Table::ASSETINDEXDATA, 'sessionId');
        $this->addColumn(Table::ASSETINDEXDATA, 'sessionId', $this->integer()->notNull()->after('id'));
        $this->addColumn(Table::ASSETINDEXDATA, 'isDir', $this->boolean()->defaultValue(false)->after('timestamp'));
        $this->addColumn(Table::ASSETINDEXDATA, 'isSkipped', $this->boolean()->defaultValue(false)->after('recordId'));

        $this->dropTableIfExists(Table::ASSETINDEXINGSESSIONS);
        $this->createTable(Table::ASSETINDEXINGSESSIONS, [
            'id' => $this->primaryKey(),
            'indexedVolumes' => $this->text(),
            'totalEntries' => $this->integer(),
            'processedEntries' => $this->integer()->notNull()->defaultValue(0),
            'cacheRemoteImages' => $this->boolean(),
            'isCli' => $this->boolean()->defaultValue(false),
            'actionRequired' => $this->boolean()->defaultValue(false),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->addForeignKey(null, Table::ASSETINDEXDATA, ['sessionId'], Table::ASSETINDEXINGSESSIONS, ['id'], 'CASCADE', null);

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m210121_145800_asset_indexing_changes cannot be reverted.\n";
        return false;
    }
}
