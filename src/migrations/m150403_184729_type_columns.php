<?php

namespace craft\migrations;

use craft\db\Migration;
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
        $componentTypes = [
            [
                'namespace' => 'craft\volumes',
                'tables' => [
                    '{{%assetsources}}',
                ],
                'classes' => [
                    'GoogleCloud',
                    'Local',
                    'Rackspace',
                    'S3',
                ]
            ],
            [
                'namespace' => 'craft\elements',
                'tables' => [
                    '{{%elements}}',
                    '{{%elementindexsettings}}',
                    '{{%fieldlayouts}}',
                    '{{%templatecachecriteria}}',
                ],
                'classes' => [
                    'Asset',
                    'Category',
                    'Entry',
                    'GlobalSet',
                    'MatrixBlock',
                    'Tag',
                    'User',
                ]
            ],
            [
                'namespace' => 'craft\fields',
                'tables' => [
                    '{{%fields}}',
                ],
                'classes' => [
                    'Assets',
                    'Categories',
                    'Checkboxes',
                    'Color',
                    'Date',
                    'Dropdown',
                    'Entries',
                    'Lightswitch',
                    'Matrix',
                    'MultiSelect',
                    'Number',
                    'PlainText',
                    'PositionSelect',
                    'RadioButtons',
                    'RichText',
                    'Table',
                    'Tags',
                    'Users',
                ]
            ],
            [
                'namespace' => 'craft\widgets',
                'tables' => [
                    '{{%widgets}}',
                ],
                'classes' => [
                    'Feed',
                    'GetHelp',
                    'NewUsers',
                    'QuickPost',
                    'RecentEntries',
                    'Updates',
                ]
            ],
        ];

        foreach ($componentTypes as $componentType) {
            $columns = [
                'type' => new Expression('concat(\'' . addslashes($componentType['namespace'] . '\\') . '\', type)')
            ];

            $condition = ['type' => $componentType['classes']];

            foreach ($componentType['tables'] as $table) {
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
