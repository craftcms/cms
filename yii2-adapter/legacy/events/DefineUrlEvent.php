<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\base\Event;

/**
 * Define URL event class
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.3.0
 * @deprecated 6.0.0 Use {@see \CraftCms\Cms\Element\Events\ElementUrlResolving} or {@see \CraftCms\Cms\Element\Events\ElementUrlResolved} instead.
 */
class DefineUrlEvent extends Event
{
    /**
     * @var string|null The URL
     */
    public ?string $url = null;
}
