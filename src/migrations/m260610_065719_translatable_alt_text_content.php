<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Query;
use craft\db\Table;
use craft\helpers\Db;

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
        // Find assets that have globally-defined alt text and null/missing translated alt text,
        // and fill in their translated values.

        $query = (new Query())
            // fetch assets_sites.assetId so we know whether the row already exists
            ->select(['assets.id', 'elements_sites.siteId', 'assets.alt', 'assets_sites.assetId'])
            ->from(['assets' => Table::ASSETS])
            ->innerJoin(['elements_sites' => Table::ELEMENTS_SITES], '[[elements_sites.elementId]] = [[assets.id]]')
            ->leftJoin(['assets_sites' => Table::ASSETS_SITES], [
                'and',
                '[[assets_sites.assetId]] = [[assets.id]]',
                '[[assets_sites.siteId]] = [[elements_sites.siteId]]',
            ])
            ->where(['not', ['assets.alt' => null]])
            ->andWhere(['not', ['assets.alt' => '']])
            ->andWhere(['assets_sites.alt' => null]);

        foreach (Db::each($query) as $row) {
            // If the assets_sites.assetId value came back, the row already exists
            if (isset($row['assetId'])) {
                Db::update(Table::ASSETS_SITES, [
                    'alt' => $row['alt'],
                ], [
                    'assetId' => $row['id'],
                    'siteId' => $row['siteId'],
                ]);
            } else {
                Db::insert(Table::ASSETS_SITES, [
                    'assetId' => $row['id'],
                    'siteId' => $row['siteId'],
                    'alt' => $row['alt'],
                ]);
            }
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
