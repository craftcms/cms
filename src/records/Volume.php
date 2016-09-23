<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\records;

use yii\db\ActiveQueryInterface;
use craft\app\db\ActiveRecord;
use craft\app\validators\HandleValidator;

/**
 * Class Volume record.
 *
 * @property integer     $id            ID
 * @property integer     $fieldLayoutId Field layout ID
 * @property string      $name          Name
 * @property string      $handle        Handle
 * @property string      $type          Type
 * @property boolean     $hasUrls       Whether Volume has URLs
 * @property string      $url           URL
 * @property array       $settings      Settings
 * @property string      $sortOrder     Sort order
 * @property FieldLayout $fieldLayout   Field layout
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class Volume extends ActiveRecord

{

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [
                ['handle'],
                HandleValidator::class,
                'reservedWords' => [
                    'id',
                    'dateCreated',
                    'dateUpdated',
                    'uid',
                    'title'
                ]
            ],
            [
                ['fieldLayoutId'],
                'number',
                'min' => -2147483648,
                'max' => 2147483647,
                'integerOnly' => true
            ],
            [['name', 'handle'], 'unique'],
            [['name', 'handle', 'type'], 'required'],
            [['hasUrls'], 'boolean'],
            [['name', 'handle', 'url'], 'string', 'max' => 255],
            [['type'], 'string', 'max' => 150],
        ];
    }

    /**
     * @inheritdoc
     *
     * @return string
     */
    public static function tableName()
    {
        return '{{%volumes}}';
    }

    /**
     * Returns the asset volume’s fieldLayout.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getFieldLayout()
    {
        return $this->hasOne(FieldLayout::class, ['id' => 'fieldLayoutId']);
    }
}
