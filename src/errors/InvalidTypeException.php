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
 * @since 3.0
 */
class InvalidTypeException extends Exception
{
    /**
     * @var string|null The invalid class name
     */
    public $class;

    /**
     * @var string|null The base class or interface that [[$class]] was supposed to be
     */
    public $requiredType;

    /**
     * Constructor.
     *
     * @param string $handle The class that doesn’t exist or doesn’t extend/implement $requiredType
     * @param string $requiredType The base class or interface that $class was supposed to be
     * @param string|null $message The error message
     * @param int $code The error code
     */
    public function __construct(string $handle, string $requiredType, string $message = null, int $code = 0)
    {
        $this->class = $handle;
        $this->requiredType = $requiredType;

        if ($message === null) {
            $message = "{$handle} doesn’t exist or doesn’t extend/implement {$requiredType}";
        }

        parent::__construct($message, $code);
    }

    /**
     * @return string the user-friendly name of this exception
     */
    public function getName()
    {
        return 'Invalid component type';
    }
}
