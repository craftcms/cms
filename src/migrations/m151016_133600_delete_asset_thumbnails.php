<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;
use craft\helpers\FileHelper;

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
        Craft::info('Deleting Asset thumbnails', __METHOD__);
        $folder = Craft::$app->getPath()->getAssetsPath().DIRECTORY_SEPARATOR.'thumbs';
        FileHelper::removeDirectory($folder);
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