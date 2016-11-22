<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;
use craft\helpers\Io;
use yii\db\Expression;

/**
 * m150403_184729_type_columns migration.
 */
class m150403_184729_type_columns extends Migration
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $map = [
            'craft\volumes' => ['{{%assetsources}}'],
            //'craft\volumes'  => ['{{%volumes}}'],
            'craft\elements' => [
                '{{%elements}}',
                '{{%elementindexsettings}}',
                '{{%fieldlayouts}}',
                '{{%templatecachecriteria}}'
            ],
            'craft\fields' => ['{{%fields}}'],
            'craft\tasks' => ['{{%tasks}}'],
            'craft\widgets' => ['{{%widgets}}'],
        ];

        foreach ($map as $namespace => $tables) {
            $folderPath = Craft::getAlias('@app/'.substr($namespace, 6));
            $files = Io::getFolderContents($folderPath, false);
            $classes = [];

            foreach ($files as $file) {
                $class = Io::getFilename($file, false);
                if (strncmp($class, 'Base', 4) !== 0) {
                    $classes[] = $class;
                }
            }

            $columns = [
                'type' => new Expression('concat(\''.addslashes($namespace.'\\').'\', type)')
            ];

            $condition = ['type' => $classes];

            foreach ($tables as $table) {
                $this->alterColumn($table, 'type', $this->string()->notNull());
                $this->update($table, $columns, $condition, [], false);
            }
        }

        // S3 is now AwsS3
        $this->update('{{%assetsources}}',
            ['type' => 'craft\volumes\AwsS3'],
            ['type' => 'craft\volumes\S3']);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m150403_184729_type_columns cannot be reverted.\n";

        return false;
    }
}
