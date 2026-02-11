<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\base\Event;

/**
 * RegisterElementSearchableAttributesEvent class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 * @deprecated 6.0.0 Use {@see \CraftCms\Cms\Element\Events\RegisterSearchableAttributes} instead.
 */
class RegisterElementSearchableAttributesEvent extends Event
{
    /**
     * @var array List of registered searchable attributes for the element type.
     */
    public array $attributes = [];
}
