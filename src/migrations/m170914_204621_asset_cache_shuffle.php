<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;
use craft\helpers\FileHelper;

/**
 * m170914_204621_asset_cache_shuffle migration.
 */
class m170914_204621_asset_cache_shuffle extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $basePath = Craft::$app->getPath()->getAssetsPath();
        $oldBasePath = $basePath.DIRECTORY_SEPARATOR.'cache';

        if (!file_exists($oldBasePath)) {
            return;
        }

        foreach (['icons', 'sources'] as $dir) {
            $oldPath = $oldBasePath.DIRECTORY_SEPARATOR.$dir;
            $newPath = $basePath.DIRECTORY_SEPARATOR.$dir;

            if (file_exists($oldPath) && !file_exists($newPath)) {
                rename($oldPath, $newPath);
            }
        }

        // Nuke the entire cache/ folder, including the no-longer-needed cache/resized/
        FileHelper::removeDirectory($oldBasePath);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m170914_204621_asset_cache_shuffle cannot be reverted.\n";
        return false;
    }
}
