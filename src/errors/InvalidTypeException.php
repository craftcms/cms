<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\errors;

use yii\base\Exception;

/**
 * Class InvalidTypeException
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class InvalidTypeException extends Exception
{
    /**
     * Constructor.
     *
     * @param class-string $class The class that doesn’t exist or doesn’t extend/implement $requiredType
     * @param class-string $requiredType The base class or interface that $class was supposed to be
     * @param string|null $message The error message
     * @param int $code The error code
     */
    public function __construct(public string $class, public string $requiredType, ?string $message = null, int $code = 0)
    {
        if ($message === null) {
            $message = "{$this->class} doesn’t exist or doesn’t extend/implement {$this->requiredType}";
        }

        parent::__construct($message, $code);
    }

    /**
     * @return string the user-friendly name of this exception
     */
    public function getName(): string
    {
        return 'Invalid component type';
    }
}
