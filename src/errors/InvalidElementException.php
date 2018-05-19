<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\errors;

use craft\base\ElementInterface;
use yii\base\Exception;

/**
 * InvalidElementException represents an exception caused by setting an invalid element.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class InvalidElementException extends Exception
{
    /**
     * @var ElementInterface The element
     */
    public $element;

    /**
     * Constructor.
     *
     * @param ElementInterface $element The element
     * @param string|null $message The error message
     * @param int $code The error code
     */
    public function __construct(ElementInterface $element, string $message = null, int $code = 0)
    {
        $this->element = $element;

        if ($message === null) {
            $message = "The element “{$element}” is invalid.";
        }

        parent::__construct($message, $code);
    }

    /**
     * @return string the user-friendly name of this exception
     */
    public function getName(): string
    {
        return 'Invalid element';
    }
}
