<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;
use craft\helpers\FileHelper;
use yii\base\InvalidArgumentException;

/**
 * m190820_003519_flush_compiled_templates migration.
 */
class m190820_003519_flush_compiled_templates extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        try {
            FileHelper::clearDirectory(Craft::$app->getPath()->getCompiledTemplatesPath(false));
        } catch (InvalidArgumentException $e) {
            // the directory doesn't exist
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m190820_003519_flush_compiled_templates cannot be reverted.\n";
        return false;
    }
}
