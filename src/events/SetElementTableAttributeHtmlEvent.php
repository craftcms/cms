<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use yii\base\Event;

/**
 * SetElementTableAttributeHtmlEvent class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class SetElementTableAttributeHtmlEvent extends Event
{
    /**
     * @var string The table attribute associated with this event.
     */
    public string $attribute;

    /**
     * @var string|null The HTML to represent a table attribute.
     */
    public ?string $html = null;
}
