<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\mcp;

use Craft;
use craft\db\Query;
use craft\db\Table;
use Mcp\Event\ErrorEvent;
use Mcp\Event\RequestEvent;
use Mcp\Event\ResponseEvent;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * Keeps the stdio server’s project config in sync with other processes.
 *
 * Over HTTP, each request loads the project config fresh, and changes are saved as it ends. The stdio server is one
 * long-running command, so without this, changes would only be saved when it stops, with nothing else seeing them
 * until then, and it would never see changes other processes made, such as in the control panel or CLI.
 *
 * @internal
 */
class StdioEventDispatcher implements EventDispatcherInterface
{
    public function dispatch(object $event): object
    {
        if ($event instanceof RequestEvent) {
            $this->reloadChangedProjectConfig();
        } elseif ($event instanceof ResponseEvent || $event instanceof ErrorEvent) {
            Craft::$app->getProjectConfig()->flush();
        }

        return $event;
    }

    /**
     * Reloads the project config and the records cached from it if another process has changed it.
     */
    private function reloadChangedProjectConfig(): void
    {
        $configVersion = (new Query())
            ->select(['configVersion'])
            ->from([Table::INFO])
            ->scalar();

        if ($configVersion === Craft::$app->getInfo()->configVersion) {
            return;
        }

        // Reload the info row, whose configVersion acquireLock() compares against
        Craft::$app->getIsInstalled(true);
        Craft::$app->getProjectConfig()->forgetLoadedConfig();
        Craft::$app->getFields()->reset();
        Craft::$app->getEntries()->reset();
        Craft::$app->getSites()->reset();
        Craft::$app->getVolumes()->reset();
        Craft::$app->getFs()->reset();
        Craft::$app->getImageTransforms()->reset();
        Craft::$app->getElementSources()->refreshSources();
    }
}
