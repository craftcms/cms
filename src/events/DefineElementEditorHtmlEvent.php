<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\base\ElementInterface;

/**
 * DefineElementEditorHtmlEvent is used to define the HTML for an element editor.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class DefineElementEditorHtmlEvent extends DefineHtmlEvent
{
    /**
     * @var ElementInterface The element being edited.
     */
    public ElementInterface $element;
}
