<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\models;

use craft\base\Model;
use DateTime;

/**
 * AssetIndexData model class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
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
     * @var int|null Offset
     */
    public $offset;

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

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function datetimeAttributes(): array
    {
        $names = parent::datetimeAttributes();
        $names[] = 'timestamp';

        return $names;
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'volumeId', 'offset', 'number', 'size', 'recordId'], 'number', 'integerOnly' => true],
        ];
    }

    /**
     * Use the translated volume name as the string representation.
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->uri;
    }
}
