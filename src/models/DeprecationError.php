<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
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
 * @since  3.0
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
     * @var string|null Class
     */
    public $class;

    /**
     * @var string|null Method
     */
    public $method;

    /**
     * @var string|null Template
     */
    public $template;

    /**
     * @var int|null Template line
     */
    public $templateLine;

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
        $names = parent::datetimeAttributes();
        $names[] = 'lastOccurrence';

        return $names;
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'line', 'templateLine'], 'number', 'integerOnly' => true],
            [['lastOccurrence'], DateTimeValidator::class],
        ];
    }

    /**
     * Returns a simple indication of the origin of the deprecation error.
     *
     * @return string
     */
    public function getOrigin(): string
    {
        if ($this->template) {
            $file = $this->template;

            if (strpos($file, 'string:') === 0) {
                $file = substr($file, 7);
                $line = null;
            } else {
                $line = $this->templateLine;
            }
        } else {
            $file = $this->file;
            $line = $this->line;
        }

        return $file.($line ? " ({$line})" : '');
    }
}
