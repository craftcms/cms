<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\models;

use craft\base\Model;
use DateTime;

/**
 * AssetIndexData model class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class AssetIndexData extends Model
{
    // Properties
    // =========================================================================

    /**
     * @var int|null ID
     */
    public $id;

    /**
     * @var int|null Volume ID
     */
    public $volumeId;

    /**
     * @var string|null Session ID
     */
    public $sessionId;

    /**
     * @var string|null URI
     */
    public $uri;

    /**
     * @var int|null Size
     */
    public $size;

    /**
     * @var int|null Record ID
     */
    public $recordId;

    /**
     * @var DateTime|null The index timestamp
     */
    public $timestamp;

    /**
     * @var bool Is completed
     */
    public $completed = false;

    /**
     * @var bool In progress
     */
    public $inProgress = false;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function datetimeAttributes(): array
    {
        $attributes = parent::datetimeAttributes();
        $attributes[] = 'timestamp';
        return $attributes;
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        $rules = parent::rules();
        $rules[] = [['id', 'volumeId', 'number', 'size', 'recordId'], 'number', 'integerOnly' => true];
        $rules[] = [['completed', 'inProgress'], 'boolean'];
        return $rules;
    }

    /**
     * Use the translated volume name as the string representation.
     *
     * @return string
     */
    public function __toString(): string
    {
        return (string)$this->uri;
    }
}
