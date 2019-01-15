<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\models;

use Craft;
use craft\base\Model;
use craft\records\AssetTransform as AssetTransformRecord;
use craft\validators\DateTimeValidator;
use craft\validators\HandleValidator;
use craft\validators\UniqueValidator;

/**
 * The AssetTransform model class.
 *
 * @property bool $isNamedTransform Whether this is a named transform
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class AssetTransform extends Model
{
    // Properties
    // =========================================================================

    /**
     * @var int|null ID
     */
    public $id;

    /**
     * @var string|null Name
     */
    public $name;

    /**
     * @var string|null Handle
     */
    public $handle;

    /**
     * @var int|null Width
     */
    public $width;

    /**
     * @var int|null Height
     */
    public $height;

    /**
     * @var string|null Format
     */
    public $format;

    /**
     * @var \DateTime|null Dimension change time
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
     * @var string Position
     */
    public $interlace = 'none';

    /**
     * @var int|null Quality
     */
    public $quality;

    /**
     * @var string|null UID
     */
    public $uid;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'handle' => Craft::t('app', 'Handle'),
            'height' => Craft::t('app', 'Height'),
            'mode' => Craft::t('app', 'Mode'),
            'name' => Craft::t('app', 'Name'),
            'position' => Craft::t('app', 'Position'),
            'quality' => Craft::t('app', 'Quality'),
            'width' => Craft::t('app', 'Width'),
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        $rules = parent::rules();
        $rules[] = [['id', 'width', 'height', 'quality'], 'number', 'integerOnly' => true];
        $rules[] = [['dimensionChangeTime'], DateTimeValidator::class];
        $rules[] = [['handle'], 'string', 'max' => 255];
        $rules[] = [['name', 'handle', 'mode', 'position'], 'required'];
        $rules[] = [['handle'], 'string', 'max' => 255];
        $rules[] = [
            ['mode'],
            'in',
            'range' => [
                'stretch',
                'fit',
                'crop',
            ],
        ];
        $rules[] = [
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
        ];
        $rules[] = [
            ['interlace'],
            'in',
            'range' => [
                'none',
                'line',
                'plane',
                'partition',
            ],
        ];
        $rules[] = [
            ['handle'],
            HandleValidator::class,
            'reservedWords' => [
                'id',
                'dateCreated',
                'dateUpdated',
                'uid',
                'title',
            ],
        ];
        $rules[] = [
            ['name', 'handle'],
            UniqueValidator::class,
            'targetClass' => AssetTransformRecord::class,
        ];
        return $rules;
    }

    /**
     * Use the folder name as the string representation.
     *
     * @return string
     */
    public function __toString(): string
    {
        return (string)$this->name;
    }

    /**
     * Return whether this is a named transform
     *
     * @return bool
     */
    public function getIsNamedTransform(): bool
    {
        return !empty($this->name);
    }

    /**
     * Get a list of transform modes.
     *
     * @return array
     */
    public static function modes(): array
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
    public function datetimeAttributes(): array
    {
        $attributes = parent::datetimeAttributes();
        $attributes[] = 'dimensionChangeTime';
        return $attributes;
    }
}
