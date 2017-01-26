<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;

/**
 * m160817_161600_move_assets_cache migration.
 */
class m160817_161600_move_assets_cache extends Migration
{

    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $path = Craft::$app->getPath();
        $basePath = $path->getAssetsPath();
        $targetPath = $path->getAssetsCachePath();

        $folders = ['icons', 'resized', 'sources'];

        echo '    > Moving Assets cache folder to their new homes ... ';

        foreach ($folders as $folder) {
            if (is_dir($basePath.DIRECTORY_SEPARATOR.$folder)) {
                rename($basePath.DIRECTORY_SEPARATOR.$folder, $targetPath.DIRECTORY_SEPARATOR.$folder);
            }
        }

        echo "done\n";

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m160817_161600_move_assets_cache cannot be reverted.\n";

        return false;
    }
}
