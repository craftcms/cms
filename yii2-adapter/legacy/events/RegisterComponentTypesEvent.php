<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\base\Event;

/**
 * RegisterComponentTypesEvent class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 * @deprecated 6.0.0 use the relevant Laravel registration event, such as {@see \CraftCms\Cms\Element\Events\ElementTypesResolving}, {@see \CraftCms\Cms\Field\Events\FieldTypesResolving}, {@see \CraftCms\Cms\Auth\Events\AuthMethodsResolving}, or {@see \CraftCms\Cms\Dashboard\Events\WidgetTypesResolving}, instead.
 */
class RegisterComponentTypesEvent extends Event
{
    /**
     * @var string[] List of registered component types classes.
     */
    public array $types = [];
}
