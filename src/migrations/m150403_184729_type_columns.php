<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;
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
                    'GoogleCloud' => 'craft\googlecloud\Volume',
                    'Local',
                    'Rackspace' => 'craft\rackspace\Volume',
                    'S3' => 'craft\awss3\Volume',
                ]
            ],
            [
                'namespace' => 'craft\elements',
                'tables' => [
                    Table::ELEMENTS,
                    Table::ELEMENTINDEXSETTINGS,
                    Table::FIELDLAYOUTS,
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
                    Table::FIELDS,
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
                    'RichText' => 'craft\redactor\Field',
                    'Table',
                    'Tags',
                    'Users',
                ]
            ],
            [
                'namespace' => 'craft\widgets',
                'tables' => [
                    Table::WIDGETS,
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
            $nativeTypes = [];
            $pluginTypes = [];

            foreach ($componentType['classes'] as $key => $value) {
                if (is_numeric($key)) {
                    $nativeTypes[] = $value;
                } else {
                    $pluginTypes[$key] = $value;
                }
            }

            $columns = [
                'type' => new Expression('concat(\'' . addslashes($componentType['namespace'] . '\\') . '\', type)')
            ];

            $condition = ['type' => $nativeTypes];

            foreach ($componentType['tables'] as $table) {
                $this->alterColumn($table, 'type', $this->string()->notNull());
                $this->update($table, $columns, $condition, [], false);

                foreach ($pluginTypes as $oldType => $newType) {
                    $this->update($table, ['type' => $newType], ['type' => $oldType], [], false);
                }
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
