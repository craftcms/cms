<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\base\Event;

/**
 * Define basePaths event class.
 */
class DefineBasePathsEvent extends Event
{
    /**
     * @var array The component definitions
     */
    public array $basePaths = [];
}
