<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\base\Event;

/**
 * RegisterElementDefaultTableAttributesEvent class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 * @deprecated 6.0.0 Use {@see \CraftCms\Cms\Element\Events\ElementDefaultTableAttributesResolving} instead.
 */
class RegisterElementDefaultTableAttributesEvent extends Event
{
    /**
     * @var string The selected source’s key
     */
    public string $source;

    /**
     * @var string[] List of registered default table attributes for the element type.
     */
    public array $tableAttributes = [];
}
