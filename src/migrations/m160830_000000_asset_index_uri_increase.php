<?php
namespace craft\migrations;

use Craft;
use craft\db\Migration;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m160830_000000_asset_index_uri_increase extends Migration
{
    /**
     * Any migration code in here is wrapped inside of a transaction.
     *
     * @return bool
     */
    public function safeUp(): bool
    {
        Craft::info('Changing asset index data table uri column to text.', __METHOD__);
        $this->alterColumn('{{%assetindexdata}}', 'uri', 'text');
        Craft::info('Done changing asset index data table uri column to text.', __METHOD__);

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo 'm160830_000000_asset_index_uri_increase cannot be reverted.\n';

        return false;
    }
}
