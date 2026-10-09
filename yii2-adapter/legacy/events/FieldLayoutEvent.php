<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\base\Event;
use CraftCms\Cms\FieldLayout\FieldLayout;

/**
 * Field layout Event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Field\Events\FieldLayoutSaving}, {@see \CraftCms\Cms\Field\Events\FieldLayoutSaved}, {@see \CraftCms\Cms\Field\Events\FieldLayoutDeleting}, or {@see \CraftCms\Cms\Field\Events\FieldLayoutDeleted} instead.
 */
class FieldLayoutEvent extends Event
{
    /**
     * @var FieldLayout The field layout associated with this event.
     */
    public FieldLayout $layout;

    /**
     * @var bool Whether the field is brand new
     */
    public bool $isNew = false;
}
