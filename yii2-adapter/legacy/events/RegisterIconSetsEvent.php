<?php

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\base\Event;
use CraftCms\Cms\Cp\IconSets\IconSet;

/**
 * RegisterIconSetsEvent class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 *
 * @since 5.12.0
 * @deprecated in 6.0.0. Use {@see \CraftCms\Cms\Cp\Events\IconSetsResolving} instead.
 */
class RegisterIconSetsEvent extends Event
{
    /**
     * @var IconSet[] The registered custom icon sets
     */
    public array $iconSets = [];
}
