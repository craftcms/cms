<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Query;
use craft\db\Table;
use craft\helpers\Db;
use craft\records\Asset;

/**
 * m260610_065719_translatable_alt_text_content migration.
 */
class m260610_065719_translatable_alt_text_content extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        // get all asset records - we only need the id, alt and siteId
        $query = Asset::find()
            ->leftJoin('{{%elements_sites}}', '[[elements_sites.elementId]] = [[assets.id]]')
            ->select(['assets.id', 'elements_sites.siteId', 'assets.alt']);

        $assetsRecords = $query->createCommand()->queryAll();

        // for each of those records,
        foreach ($assetsRecords as $assetRecord) {
            $exists = (new Query())
                ->from(Table::ASSETS_SITES)
                ->where([
                        'assetId' => $assetRecord['id'],
                        'siteId' => $assetRecord['siteId'], ]
                )
                ->exists();

            if ($exists) {
                // if the assets_sites row exists and the alt column is null, copy over the value from the assets.alt
                Db::update(
                    Table::ASSETS_SITES,
                    [
                        'alt' => $assetRecord['alt'],
                    ],
                    [
                        'assetId' => $assetRecord['id'],
                        'siteId' => $assetRecord['siteId'],
                        'alt' => null,
                    ]
                );
            } else {
                // if the assets_sites row doesn't exist, insert one with the assets.alt value
                Db::insert(
                    Table::ASSETS_SITES,
                    [
                        'assetId' => $assetRecord['id'],
                        'siteId' => $assetRecord['siteId'],
                        'alt' => $assetRecord['alt'],
                    ]
                );
            }
            // the above ensures we're not changing the alt text developers might expect during the update,
            // but at the same time we're all set to no longer use the assets.alt column
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m260610_065719_translatable_alt_text_content cannot be reverted.\n";
        return false;
    }
}
