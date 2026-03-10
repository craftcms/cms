<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\base\Event;

/**
 * DefineAttributeHtmlEvent class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.0.0
 */
class DefineAttributeHtmlEvent extends Event
{
    /**
     * @var string The attribute associated with this event.
     */
    public string $attribute;

    /**
     * @var string|null The attribute’s HTML.
     */
    public ?string $html = null;
}
