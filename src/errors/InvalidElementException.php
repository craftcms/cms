<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\errors;

use craft\base\ElementInterface;

/**
 * InvalidElementException represents an exception caused by setting an invalid element.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class InvalidElementException extends ElementException
{
    /**
     * @inheritdoc
     */
    public function __construct(ElementInterface $element, ?string $message = null, int $code = 0)
    {
        if ($message === null) {
            $message = "The element “{$element}” is invalid.";
        }

        parent::__construct($element, $message, $code);
    }

    /**
     * @return string the user-friendly name of this exception
     */
    public function getName(): string
    {
        return 'Invalid element';
    }
}
