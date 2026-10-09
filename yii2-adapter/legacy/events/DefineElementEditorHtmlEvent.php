<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use CraftCms\Cms\Element\Contracts\ElementInterface;

/**
 * DefineElementEditorHtmlEvent is used to define the HTML for an element editor.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\Events\ElementEditorContentResolving} instead.
 */
class DefineElementEditorHtmlEvent extends DefineHtmlEvent
{
    /**
     * @var ElementInterface The element being edited.
     */
    public ElementInterface $element;
}
