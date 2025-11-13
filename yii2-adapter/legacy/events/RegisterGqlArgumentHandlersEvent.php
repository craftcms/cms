<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\base\Event;
use craft\gql\base\ArgumentHandlerInterface;

/**
 * RegisterGqlArgumentHandlersEvent class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.6.0
 */
class RegisterGqlArgumentHandlersEvent extends Event
{
    /**
     * @var array<string,class-string<ArgumentHandlerInterface>|ArgumentHandlerInterface> List of Argument handler class names.
     */
    public array $handlers = [];
}
