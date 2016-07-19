<?php

namespace craft\app\migrations;

use Craft;
use craft\app\db\Migration;
use craft\app\helpers\Io;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m151209_000000_move_logo extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $pathService = Craft::$app->getPath();
        Io::rename($pathService->getStoragePath().'/logo', $pathService->getRebrandPath().'/logo', true);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m151209_000000_move_logo cannot be reverted.\n";

        return false;
    }
}
