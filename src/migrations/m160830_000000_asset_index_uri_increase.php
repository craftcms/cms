<?php
namespace Craft;

use Craft;
use craft\app\enums\ColumnType;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m160830_000000_asset_index_uri_increase extends BaseMigration
{
    /**
     * Any migration code in here is wrapped inside of a transaction.
     *
     * @return bool
     */
    public function safeUp()
    {
        Craft::info('Changing asset index data table uri column to text.');
        $this->alterColumn('{{%assetindexdata}}', 'uri', ['column' => ColumnType::Text]);
        Craft::info('Done changing asset index data table uri column to text.');

        return true;
    }
}
