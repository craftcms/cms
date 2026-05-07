<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\errors;

use Throwable;
use yii\base\Exception;

/**
 * Class FieldNotFoundException
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.4
 */
class FieldNotFoundException extends Exception
{
    /**
     * @var int|string The field’s ID or UUID
     * @since 5.10.0
     */
    public int|string $fieldId;

    /**
     * Constructor
     *
     * @param int|string $fieldId
     * @param string $message
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct(int|string $fieldId, string $message = '', int $code = 0, ?Throwable $previous = null)
    {
        $this->fieldId = $fieldId;
        parent::__construct($message, $code, $previous);
    }

    /**
     * @return string the user-friendly name of this exception
     */
    public function getName(): string
    {
        return 'Field not found';
    }

    /**
     * @deprecated in 5.10.0
     */
    public function getFieldUid(): string
    {
        return (string)$this->fieldId;
    }
}
