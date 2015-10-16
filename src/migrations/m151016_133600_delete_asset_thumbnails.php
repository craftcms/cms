<?php

namespace craft\app\migrations;

use Craft;
use craft\app\db\Migration;
use craft\app\helpers\Io;

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