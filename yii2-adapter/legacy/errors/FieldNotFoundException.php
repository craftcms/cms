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
     * @var string The field’s UUID
     */
    public string $fieldUid;

    /**
     * Constructor
     *
     * @param string $fieldUid
     * @param string $message
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct(string $fieldUid, string $message = '', int $code = 0, ?Throwable $previous = null)
    {
        $this->fieldUid = $fieldUid;
        parent::__construct($message, $code, $previous);
    }

    /**
     * @return string the user-friendly name of this exception
     */
    public function getName(): string
    {
        return 'Field not found';
    }
}
