<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;
use craft\helpers\FileHelper;

/**
 * m170816_133741_delete_compiled_behaviors migration.
 */
class m170816_133741_delete_compiled_behaviors extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $files = ['ContentBehavior', 'ElementQueryBehavior'];
        $compiledClassesPath = Craft::$app->getPath()->getCompiledClassesPath();

        foreach ($files as $file) {
            $path = $compiledClassesPath.DIRECTORY_SEPARATOR.$file.'.php';
            if (file_exists($path)) {
                echo "    > removing $path ...";
                FileHelper::unlink($path);
                echo " done\n";
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m170816_133741_delete_compiled_behaviors cannot be reverted.\n";
        return false;
    }
}
