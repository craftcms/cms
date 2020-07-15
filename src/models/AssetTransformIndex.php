<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\models;

use Craft;
use craft\base\Model;
use craft\validators\DateTimeValidator;
use yii\base\InvalidConfigException;

/**
 * Class AssetTransformIndex model.
 *
 * @property AssetTransform $transform
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class AssetTransformIndex extends Model
{
    /**
     * @var int|null ID
     */
    public $id;

    /**
     * @var int|null File ID
     */
    public $assetId;

    /**
     * @var int|null Volume ID
     */
    public $volumeId;

    /**
     * @var string|null Filename
     */
    public $filename;

    /**
     * @var string|null Format
     */
    public $format;

    /**
     * @var string|null Location
     */
    public $location;

    /**
     * @var bool File exists
     */
    public $fileExists = false;

    /**
     * @var bool In progress
     */
    public $inProgress = false;

    /**
     * @var bool Transform generation failed
     */
    public $error = false;

    /**
     * @var \DateTime|null Date indexed
     */
    public $dateIndexed;

    /**
     * @var \DateTime|null Date updated
     */
    public $dateUpdated;

    /**
     * @var \DateTime|null Date created
     */
    public $dateCreated;

    /**
     * @var string|null Detected format
     */
    public $detectedFormat;

    /**
     * @var AssetTransform|null The transform associated with this index
     */
    private $_transform;

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
     * @return AssetTransform
     * @throws InvalidConfigException if [[location]] is invalid
     */
    public function getTransform(): AssetTransform
    {
        if ($this->_transform !== null) {
            return $this->_transform;
        }

        if (($this->_transform = Craft::$app->getAssetTransforms()->normalizeTransform(mb_substr($this->location, 1))) === null) {
            throw new InvalidConfigException('Invalid transform location: ' . $this->location);
        }

        return $this->_transform;
    }

    /**
     * Sets the transform associated with this index.
     *
     * @param AssetTransform $transform
     */
    public function setTransform(AssetTransform $transform)
    {
        $this->_transform = $transform;
    }
}
