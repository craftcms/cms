<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;
use craft\helpers\Io;

/**
 * m151016_133600_delete_asset_thumbnails migration.
 */
class m151016_133600_delete_asset_thumbnails extends Migration
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        Craft::info("Deleting Asset thumbnails");
        $folder = Craft::$app->getPath()->getAssetsPath().'/thumbs';
        Io::deleteFolder($folder);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m151016_133600_delete_asset_thumbnails cannot be reverted.\n";

        return false;
    }
}