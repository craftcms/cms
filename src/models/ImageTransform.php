<?php
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
use craft\validators\ColorValidator;
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
     * @var string The default image transformer
     */
    public const DEFAULT_TRANSFORMER = ImageTransformer::class;

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
     * @var string 'crop'|'fit'|'stretch'|'letterbox' Mode
     */
    public string $mode = 'crop';

    /**
     * @var 'top-left'|'top-center'|'top-right'|'center-left'|'center-center'|'center-right'|'bottom-left'|'bottom-center'|'bottom-right' Position
     */
    public string $position = 'center-center';

    /**
     * @var 'none'|'line'|'plane'|'partition' Interlace
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
     * @var string|null Fill color
     * @since 4.4.0
     */
    public ?string $fill = null;

    /**
     * @var bool|null Allow upscaling
     * @since 4.4.0
     */
    public ?bool $upscale = null;

    /**
     * @var string The image transformer to use.
     * @phpstan-var class-string<ImageTransformerInterface>
     */
    protected string $transformer = self::DEFAULT_TRANSFORMER;

    /**
     * @inheritdoc
     */
    public function __construct($config = [])
    {
        if (isset($config['width']) && !$config['width']) {
            unset($config['width']);
        }
        if (isset($config['height']) && !$config['height']) {
            unset($config['height']);
        }
        if (isset($config['quality']) && !$config['quality']) {
            unset($config['quality']);
        }

        parent::__construct($config);
    }

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();

        if (!isset($this->upscale)) {
            $this->upscale = Craft::$app->getConfig()->getGeneral()->upscaleImages;
        }
    }

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
            'fill' => Craft::t('app', 'Fill Color'),
            'upscale' => Craft::t('app', 'Allow Upscaling'),
            'transformer' => Craft::t('app', 'Image transformer'),
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
        $rules[] = [['fill'], ColorValidator::class];
        $rules[] = [['upscale'], 'boolean'];
        $rules[] = [
            ['mode'],
            'in',
            'range' => [
                'stretch',
                'fit',
                'crop',
                'letterbox',
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
            'letterbox' => Craft::t('app', 'Letterbox'),
        ];
    }

    /**
     * Return the image transformer for this transform.
     *
     * @return ImageTransformerInterface
     */
    public function getImageTransformer(): ImageTransformerInterface
    {
        return Craft::$app->getImageTransforms()->getImageTransformer($this->transformer);
    }

    /**
     * Returns the image transformer.
     *
     * @return string
     */
    public function getTransformer(): string
    {
        return $this->transformer;
    }

    /**
     * Sets the image transformer.
     *
     * @param string $transformer
     */
    public function setTransformer(string $transformer): void
    {
        if (!is_subclass_of($transformer, ImageTransformerInterface::class)) {
            Craft::warning("Invalid image transformer: $transformer", __METHOD__);
            $transformer = self::DEFAULT_TRANSFORMER;
        }

        $this->transformer = $transformer;
    }

    /**
     * Returns the transform’s config.
     *
     * @return array
     * @since 4.4.2
     */
    public function getConfig(): array
    {
        return [
            'fill' => $this->fill,
            'format' => $this->format,
            'handle' => $this->handle,
            'height' => $this->height,
            'interlace' => $this->interlace,
            'mode' => $this->mode,
            'name' => $this->name,
            'position' => $this->position,
            'quality' => $this->quality,
            'upscale' => $this->upscale ?? Craft::$app->getConfig()->getGeneral()->upscaleImages,
            'width' => $this->width,
        ];
    }
}
