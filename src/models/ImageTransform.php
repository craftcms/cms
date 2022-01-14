<?php
declare(strict_types = 1);
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\models;

use Craft;
use craft\base\imagetransforms\ImageTransformerInterface;
use craft\base\Model;
use craft\imagetransforms\ImageTransformer;
use craft\records\ImageTransform as ImageTransformRecord;
use craft\validators\DateTimeValidator;
use craft\validators\HandleValidator;
use craft\validators\UniqueValidator;
use DateTime;

/**
 * The ImageTransform model class.
 *
 * @property bool $isNamedTransform Whether this is a named transform
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class ImageTransform extends Model
{
    /**
     * @var string The default image transform driver.
     */
    public const DEFAULT_DRIVER = ImageTransformer::class;

    /**
     * @var int|null ID
     */
    public ?int $id = null;

    /**
     * @var string|null Name
     */
    public ?string $name = null;

    /**
     * @var string|null Handle
     */
    public ?string $handle = null;

    /**
     * @var int|null Width
     */
    public ?int $width = null;

    /**
     * @var int|null Height
     */
    public ?int $height = null;

    /**
     * @var string|null Format
     */
    public ?string $format = null;

    /**
     * @var DateTime|null Dimension change time
     */
    public ?DateTime $parameterChangeTime = null;

    /**
     * @var string Mode
     */
    public string $mode = 'crop';

    /**
     * @var string Position
     */
    public string $position = 'center-center';

    /**
     * @var string Position
     */
    public string $interlace = 'none';

    /**
     * @var int|null Quality
     */
    public ?int $quality = null;

    /**
     * @var string|null UID
     */
    public ?string $uid = null;

    /**
     * The Image Transform driver to use for image transforms.
     *
     * @var string
     */
    protected string $driver = self::DEFAULT_DRIVER;

    /**
     * @inheritdoc
     */
    public function attributeLabels(): array
    {
        return [
            'handle' => Craft::t('app', 'Handle'),
            'height' => Craft::t('app', 'Height'),
            'mode' => Craft::t('app', 'Mode'),
            'name' => Craft::t('app', 'Name'),
            'position' => Craft::t('app', 'Position'),
            'quality' => Craft::t('app', 'Quality'),
            'width' => Craft::t('app', 'Width'),
            'driver' => Craft::t('app', 'Image transform driver'),
        ];
    }

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['id', 'width', 'height', 'quality'], 'number', 'integerOnly' => true];
        $rules[] = [['parameterChangeTime'], DateTimeValidator::class];
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
            'targetClass' => ImageTransformRecord::class,
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
            'stretch' => Craft::t('app', 'Stretch to fit'),
        ];
    }

    /**
     * @inheritdoc
     */
    public function datetimeAttributes(): array
    {
        $attributes = parent::datetimeAttributes();
        $attributes[] = 'parameterChangeTime';
        return $attributes;
    }

    /**
     * Return the image transformer for this transform.
     *
     * @return ImageTransformerInterface
     */
    public function getImageTransformer(): ImageTransformerInterface
    {
        return Craft::$app->getImageTransforms()->getImageTransformer($this->driver);
    }

    /**
     * Get the transform driver.
     *
     * @return string
     */
    public function getDriver(): string
    {
        return $this->driver;
    }

    /**
     * Set the transform driver.
     *
     * @param string $imageTransformDriver
     */
    public function setDriver(string $imageTransformDriver): void
    {
        if (!is_subclass_of($imageTransformDriver, ImageTransformerInterface::class)) {
            Craft::warning($imageTransformDriver . ' is not a valid image transform driver.');
            $imageTransformDriver = self::DEFAULT_DRIVER;
        }

        $this->driver = $imageTransformDriver;
    }
}
