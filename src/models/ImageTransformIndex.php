<?php
declare(strict_types=1);
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\models;

use Craft;
use craft\base\ImageTransformDriverInterface;
use craft\base\Model;
use craft\validators\DateTimeValidator;
use DateTime;
use yii\base\InvalidConfigException;

/**
 * Class ImageTransformIndexs model.
 *
 * @property-read null|ImageTransformDriverInterface $imageTransformer
 * @property ImageTransform $transform
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class ImageTransformIndex extends Model
{
    /**
     * @var int|null ID
     */
    public ?int $id = null;

    /**
     * @var int|null File ID
     */
    public ?int $assetId = null;

    /**
     * @var string The transform driver
     */
    public string $driver = ImageTransform::DEFAULT_DRIVER;

    /**
     * @var string|null Filename
     */
    public ?string $filename = null;

    /**
     * @var string|null Format
     */
    public ?string $format = null;

    /**
     * @var string|null Location
     */
    public ?string $transformString = null;

    /**
     * @var bool File exists
     */
    public bool $fileExists = false;

    /**
     * @var bool In progress
     */
    public bool $inProgress = false;

    /**
     * @var bool Transform generation failed
     */
    public bool $error = false;

    /**
     * @var DateTime|null Date indexed
     */
    public ?DateTime $dateIndexed = null;

    /**
     * @var DateTime|null Date updated
     */
    public ?DateTime $dateUpdated = null;

    /**
     * @var DateTime|null Date created
     */
    public ?DateTime $dateCreated = null;

    /**
     * @var string|null Detected format
     */
    public ?string $detectedFormat = null;

    /**
     * @var ImageTransform|null The transform associated with this index
     */
    private ?ImageTransform $_transform = null;

    /**
     * @inheritdoc
     */
    public function datetimeAttributes(): array
    {
        $attributes = parent::datetimeAttributes();
        $attributes[] = 'dateIndexed';
        return $attributes;
    }

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['id', 'assetId', 'volumeId'], 'number', 'integerOnly' => true];
        $rules[] = [['dateIndexed', 'dateUpdated', 'dateCreated'], DateTimeValidator::class];
        return $rules;
    }

    /**
     * Use the folder name as the string representation.
     *
     * @return string
     */
    public function __toString(): string
    {
        return (string)$this->id;
    }

    /**
     * Returns the transform associated with this index.
     *
     * @return ImageTransform
     * @throws InvalidConfigException if [[transformString]] is invalid
     */
    public function getTransform(): ImageTransform
    {
        if (isset($this->_transform)) {
            return $this->_transform;
        }

        if (($this->_transform = Craft::$app->getAssetTransforms()->normalizeTransform($this->transformString)) === null) {
            throw new InvalidConfigException('Invalid transform string: ' . $this->transformString);
        }

        return $this->_transform;
    }

    /**
     * Sets the transform associated with this index.
     *
     * @param ImageTransform $transform
     */
    public function setTransform(ImageTransform $transform): void
    {
        $this->_transform = $transform;
    }

    /**
     * Return the image transformer for this transform.
     *
     * @return ImageTransformDriverInterface
     * @since 4.0.0
     */
    public function getImageTransformer(): ImageTransformDriverInterface
    {
        return Craft::$app->getAssetTransforms()->getImageTransformer($this->driver);
    }

}
