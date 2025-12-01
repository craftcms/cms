<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\base\ElementInterface;

/**
 * DefineFieldHtmlEvent is used to define the HTML for a field input.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.5.0
 */
class DefineFieldHtmlEvent extends DefineHtmlEvent
{
    /**
     * @var mixed The field’s value
     */
    public mixed $value;

    /**
     * @var ElementInterface|null The element the field is associated with, if there is one
     */
    public ?ElementInterface $element = null;

    /**
     * @var bool Whether this is for an inline edit form.
     * @since 5.0.0
     */
    public bool $inline;
}
