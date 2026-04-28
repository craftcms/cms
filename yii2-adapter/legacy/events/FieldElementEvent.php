<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use CraftCms\Cms\Element\Contracts\ElementInterface;

/**
 * FieldElementEvent class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Field\Events\FieldElementEvent} instead.
 */
class FieldElementEvent extends ModelEvent
{
    /**
     * @var ElementInterface The element associated with this event
     */
    public ElementInterface $element;
}
