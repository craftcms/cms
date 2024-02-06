<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\models;

use craft\base\Model;
use craft\validators\DateTimeValidator;
use DateTime;

/**
 * DeprecationError model.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class DeprecationError extends Model
{
    /**
     * @var int|null ID
     */
    public ?int $id = null;

    /**
     * @var string|null Key
     */
    public ?string $key = null;

    /**
     * @var string|null Fingerprint
     */
    public ?string $fingerprint = null;

    /**
     * @var DateTime|null Last occurrence
     */
    public ?DateTime $lastOccurrence = null;

    /**
     * @var string|null File
     */
    public ?string $file = null;

    /**
     * @var int|null Line
     */
    public ?int $line = null;

    /**
     * @var string|null Message
     */
    public ?string $message = null;

    /**
     * @var array|null Traces
     */
    public ?array $traces = null;

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['id', 'line'], 'number', 'integerOnly' => true];
        $rules[] = [['lastOccurrence'], DateTimeValidator::class];
        return $rules;
    }
}
