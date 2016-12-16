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
     * @var integer ID
     */
    public $id;

    /**
     * @var integer Volume ID
     */
    public $volumeId;

    /**
     * @var string Session ID
     */
    public $sessionId;

    /**
     * @var integer Offset
     */
    public $offset;

    /**
     * @var string URI
     */
    public $uri;

    /**
     * @var integer Size
     */
    public $size;

    /**
     * @var integer Record ID
     */
    public $recordId;

    /**
     * @var DateTime The index timestamp
     */
    public $timestamp;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function datetimeAttributes()
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
    public function __toString()
    {
        return $this->uri;
    }
}
