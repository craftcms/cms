<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\base\Event;

/**
 * DefineGqlArgumentsEvent class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.9.0
 */
class DefineGqlArgumentsEvent extends Event
{
    /**
     * @var array<array|object> List of arguments
     */
    public array $arguments = [];
}
