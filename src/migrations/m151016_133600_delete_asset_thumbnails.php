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
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $folder = Craft::$app->getPath()->getAssetsPath() . DIRECTORY_SEPARATOR . 'thumbs';
        echo "    > Removing directory: {$folder} ... ";
        FileHelper::removeDirectory($folder);
        echo "done\n";
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
