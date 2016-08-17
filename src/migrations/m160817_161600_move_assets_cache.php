<?php

namespace craft\app\migrations;

use Craft;
use craft\app\db\Migration;
use craft\app\helpers\Io;

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

        Craft::info('Moving Assets cache folder to their new homes!');

        foreach ($folders as $folder)
        {
            Io::move($basePath.'/'.$folder, $targetPath.'/'.$folder);
        }

        Craft::info('All done');

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo 'm160817_161600_move_assets_cache cannot be reverted.\n';
        return false;
    }
}
