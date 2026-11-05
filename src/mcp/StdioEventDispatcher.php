<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\mcp;

use Craft;
use Mcp\Event\ErrorEvent;
use Mcp\Event\ResponseEvent;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * Saves project config changes before each stdio response is sent.
 *
 * Over HTTP, changes are saved as each request ends. The stdio server is one
 * long-running command, so without this they’d only be saved when it stops,
 * and nothing else would see them until then.
 *
 * @internal
 */
class StdioEventDispatcher implements EventDispatcherInterface
{
    public function dispatch(object $event): object
    {
        if ($event instanceof ResponseEvent || $event instanceof ErrorEvent) {
            Craft::$app->getProjectConfig()->flush();
        }

        return $event;
    }
}
