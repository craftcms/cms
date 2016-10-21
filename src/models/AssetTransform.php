<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\models;

use Craft;
use craft\app\base\Model;
use craft\app\records\AssetTransform as AssetTransformRecord;
use craft\app\validators\DateTimeValidator;
use craft\app\validators\HandleValidator;
use craft\app\validators\UniqueValidator;

/**
 * The AssetTransform model class.
 *
 * @property boolean $isNamedTransform Whether this is a named transform
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class AssetTransform extends Model
{
    // Properties
    // =========================================================================

    /**
     * @var integer ID
     */
    public $id;

    /**
     * @var string Name
     */
    public $name;

    /**
     * @var string Handle
     */
    public $handle;

    /**
     * @var integer Width
     */
    public $width;

    /**
     * @var integer Height
     */
    public $height;

    /**
     * @var string Format
     */
    public $format;

    /**
     * @var \DateTime Dimension change time
     */
    public $dimensionChangeTime;

    /**
     * @var string Mode
     */
    public $mode = 'crop';

    /**
     * @var string Position
     */
    public $position = 'center-center';

    /**
     * @var integer Quality
     */
    public $quality;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'width', 'height', 'quality'], 'number', 'integerOnly' => true],
            [['dimensionChangeTime'], DateTimeValidator::class],
            [['handle'], 'string', 'max' => 255],
            [['name', 'handle', 'mode', 'position'], 'required'],
            [['handle'], 'string', 'max' => 255],
            [
                ['mode'],
                'in',
                'range' => [
                    'stretch',
                    'fit',
                    'crop',
                ],
            ],
            [
                ['position'],
                'in',
                'range' => [
                    'top-left',
                    'top-center',
                    'top-right',
                    'center-left',
                    'center-center',
                    'center-right',
                    'bottom-left',
                    'bottom-center',
                    'bottom-right',
                ],
            ],
            [
                ['handle'],
                HandleValidator::class,
                'reservedWords' => [
                    'id',
                    'dateCreated',
                    'dateUpdated',
                    'uid',
                    'title',
                ],
            ],
            [
                ['name', 'handle'],
                UniqueValidator::class,
                'targetClass' => AssetTransformRecord::class,
            ],
        ];
    }

    /**
     * Use the folder name as the string representation.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->name;
    }

    /**
     * Return whether this is a named transform
     *
     * @return boolean
     */
    public function getIsNamedTransform()
    {
        return !empty($this->name);
    }

    /**
     * Get a list of transform modes.
     *
     * @return array
     */
    public static function getTransformModes()
    {
        return [
            'crop' => Craft::t('app', 'Scale and crop'),
            'fit' => Craft::t('app', 'Scale to fit'),
            'stretch' => Craft::t('app', 'Stretch to fit')
        ];
    }

    /**
     * @inheritdoc
     */
    public function datetimeAttributes()
    {
        $names = parent::datetimeAttributes();
        $names[] = 'dimensionChangeTime';

        return $names;
    }
}
