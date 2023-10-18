<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\models;

use craft\base\imagetransforms\ImageTransformerInterface;
use craft\base\Model;
use craft\helpers\ImageTransforms;
use craft\validators\DateTimeValidator;
use DateTime;
use yii\base\InvalidConfigException;

/**
 * Class ImageTransformIndex model.
 *
 * @property ImageTransform $transform
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class ImageTransformIndex extends Model
{
    /**
     * @var int|null ID
     */
    public ?int $id = null;

    /**
     * @var int|null Asset ID
     */
    public ?int $assetId = null;

    /**
     * @var string The image transformer
     * @phpstan-var class-string<ImageTransformerInterface>
     */
    public string $transformer = ImageTransform::DEFAULT_TRANSFORMER;

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
    public function init(): void
    {
        parent::init();

        // Only respect inProgress if it's been less than 30 seconds since the last time the index was updated
        if ($this->inProgress) {
            $duration = time() - ($this->dateUpdated?->getTimestamp() ?? 0);
            if ($duration > 30) {
                $this->inProgress = false;
            }
        }
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

        if (($this->_transform = ImageTransforms::normalizeTransform($this->transformString)) === null) {
            throw new InvalidConfigException('Invalid transform string: ' . $this->transformString);
        }

        if ($this->format) {
            $this->_transform->format = $this->format;
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
}
