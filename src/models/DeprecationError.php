<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\models;

use craft\base\Model;
use craft\helpers\Json;
use craft\validators\DateTimeValidator;
use DateTime;

/**
 * DeprecationError model.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class DeprecationError extends Model
{
    // Properties
    // =========================================================================

    /**
     * @var int|null ID
     */
    public $id;

    /**
     * @var string|null Key
     */
    public $key;

    /**
     * @var string|null Fingerprint
     */
    public $fingerprint;

    /**
     * @var DateTime|null Last occurrence
     */
    public $lastOccurrence;

    /**
     * @var string|null File
     */
    public $file;

    /**
     * @var int|null Line
     */
    public $line;

    /**
     * @var string|null Message
     */
    public $message;

    /**
     * @var array|null Traces
     */
    public $traces;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        if (is_string($this->traces)) {
            $this->traces = Json::decode($this->traces);
        }
    }

    /**
     * @inheritdoc
     */
    public function datetimeAttributes(): array
    {
        $attributes = parent::datetimeAttributes();
        $attributes[] = 'lastOccurrence';
        return $attributes;
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'line'], 'number', 'integerOnly' => true],
            [['lastOccurrence'], DateTimeValidator::class],
        ];
    }
}
