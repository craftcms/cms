<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\base\Event;

/**
 * Define entry types event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.6.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Entry\Events\DefineEntryTypes} instead.
 */
class DefineEntryTypesEvent extends Event
{
    /**
     * @var array The available entry types
     */
    public array $entryTypes = [];
}
