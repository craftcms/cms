<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Table;
use craft\helpers\Db;
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
        // alter elements_sites table - add alt
        $this->addColumn(Table::ELEMENTS_SITES, 'alt', $this->text()->after('uri'));

        // migrate data from assets.alt to elements_sites.alt
        $assetRecords = AssetRecord::find()
            ->select(['id', 'alt'])
            ->where('alt IS NOT NULL')
            ->orderBy('id ASC')
            ->asArray()
            ->all();


        if (!empty($assetRecords)) {
            $db = Craft::$app->getDb();
            foreach ($assetRecords as $record) {
                $db->createCommand()
                    ->update(
                        Table::ELEMENTS_SITES,
                        ['alt' => $record['alt']],
                        ['elementId' => $record['id']]
                    )
                    ->execute();
            }
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
