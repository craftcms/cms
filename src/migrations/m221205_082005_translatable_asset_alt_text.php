<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Table;
use craft\helpers\Db;
use craft\helpers\StringHelper;
use craft\records\Asset as AssetRecord;

/**
 * m221205_082005_translatable_asset_alt_text migration.
 */
class m221205_082005_translatable_asset_alt_text extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $this->createTable(Table::ASSETS_SITES, [
            'id' => $this->primaryKey(),
            'assetId' => $this->integer()->notNull(),
            'siteId' => $this->integer()->notNull(),
            'alt' => $this->text(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->addForeignKey(null, Table::ASSETS_SITES, ['assetId'], Table::ASSETS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::ASSETS_SITES, ['siteId'], Table::SITES, ['id'], 'CASCADE', 'CASCADE');

        // migrate data from assets.alt to elements_sites.alt
        $assetRecords = AssetRecord::find()
            ->select(['id', 'alt'])
            ->where('alt IS NOT NULL')
            ->orderBy('id ASC')
            ->asArray()
            ->all();

        $siteIds = Craft::$app->getSites()->getAllSiteIds(true);

        $now = Db::prepareDateForDb(new \DateTime('now'));
        $data = [];
        foreach ($assetRecords as $assetRecord) {
            foreach ($siteIds as $siteId) {
                $data[] = [
                    'assetId' => $assetRecord['id'],
                    'siteId' => $siteId,
                    'alt' => $assetRecord['alt'],
                    'dateCreated' => $now,
                    'dateUpdated' => $now,
                    'uid' => StringHelper::UUID(),
                ];
            }
        }


        if (!empty($data)) {
            $db = Craft::$app->getDb();
            $db->createCommand()
                ->batchInsert(Table::ASSETS_SITES, ['assetId', 'siteId', 'alt', 'dateCreated', 'dateUpdated', 'uid'], $data)
                ->execute();
        }

        // remove assets.alt
        $this->dropColumn(Table::ASSETS, 'alt');

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m221205_082005_translatable_asset_alt_text cannot be reverted.\n";
        return false;
    }
}
