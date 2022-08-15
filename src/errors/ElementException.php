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
 * ElementException represents an exception involving an element.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.4.29
 */
class ElementException extends Exception
{
    /**
     * @var ElementInterface The element
     */
    public ElementInterface $element;

    /**
     * Constructor.
     *
     * @param ElementInterface $element The element
     * @param string|null $message The error message
     * @param int $code The error code
     */
    public function __construct(ElementInterface $element, ?string $message = null, int $code = 0)
    {
        $this->element = $element;
        parent::__construct($message, $code);
    }
}
