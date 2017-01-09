<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\models;

use craft\base\Model;
use craft\validators\DateTimeValidator;

/**
 * Class AssetTransformIndex model.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class AssetTransformIndex extends Model
{
    // Properties
    // =========================================================================

    /**
     * @var int ID
     */
    public $id;

    /**
     * @var int File ID
     */
    public $assetId;

    /**
     * @var int Volume ID
     */
    public $volumeId;

    /**
     * @var string Filename
     */
    public $filename;

    /**
     * @var string Format
     */
    public $format;

    /**
     * @var string Location
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
     * @var \DateTime Date indexed
     */
    public $dateIndexed;

    /**
     * @var \DateTime Date updated
     */
    public $dateUpdated;

    /**
     * @var \DateTime Date created
     */
    public $dateCreated;

    /**
     * @var string Detected format
     */
    public $detectedFormat;

    /**
     * @var AssetTransform|null Transform
     */
    public $transform;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function datetimeAttributes(): array
    {
        $names = parent::datetimeAttributes();
        $names[] = 'dateIndexed';

        return $names;
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'assetId', 'volumeId'], 'number', 'integerOnly' => true],
            [['dateIndexed', 'dateUpdated', 'dateCreated'], DateTimeValidator::class],
        ];
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
}
