<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\errors;

use yii\base\Exception;

/**
 * Class InvalidTypeException
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.exceptions
 * @since     3.0
 */
class InvalidTypeException extends Exception
{
    /**
     * @var string The invalid class name
     */
    public $class;

    /**
     * @var string The base class or interface that [[$class]] was supposed to be
     */
    public $requiredType;

    /**
     * Constructor.
     *
     * @param string  $class        The class that doesn’t exist or doesn’t extend/implement $requiredType
     * @param string  $requiredType The base class or interface that $class was supposed to be
     * @param string  $message      The error message
     * @param integer $code         The error code
     */
    public function __construct($class, $requiredType, $message = null, $code = 0)
    {
        $this->class = $class;
        $this->requiredType = $requiredType;

        if ($message === null) {
            $message = "{$class} doesn’t exist or doesn’t extend/implement {$requiredType}";
        }

        parent::__construct($message, $code);
    }
}
