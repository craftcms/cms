<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use Craft;
use craft\db\Query;
use craft\errors\RouteNotFoundException;
use craft\events\RouteEvent;
use craft\helpers\ArrayHelper;
use craft\helpers\Json;
use craft\records\Route as RouteRecord;
use yii\base\Component;

/**
 * Routes service.
 * An instance of the Routes service is globally accessible in Craft via [[\craft\base\ApplicationTrait::getRoutes()|<code>Craft::$app->routes</code>]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Routes extends Component
{
    // Constants
    // =========================================================================

    /**
     * @event RouteEvent The event that is triggered before a route is saved.
     */
    const EVENT_BEFORE_SAVE_ROUTE = 'beforeSaveRoute';

    /**
     * @event RouteEvent The event that is triggered after a route is saved.
     */
    const EVENT_AFTER_SAVE_ROUTE = 'afterSaveRoute';

    /**
     * @event RouteEvent The event that is triggered before a route is deleted.
     */
    const EVENT_BEFORE_DELETE_ROUTE = 'beforeDeleteRoute';

    /**
     * @event RouteEvent The event that is triggered after a route is deleted.
     */
    const EVENT_AFTER_DELETE_ROUTE = 'afterDeleteRoute';

    // Public Methods
    // =========================================================================

    /**
     * Returns the routes defined in `config/routes.php`
     *
     * @return array
     */
    public function getConfigFileRoutes(): array
    {
        $path = Craft::$app->getPath()->getConfigPath().DIRECTORY_SEPARATOR.'routes.php';

        if (!file_exists($path)) {
            return [];
        }

        $routes = require $path;

        if (!is_array($routes)) {
            return [];
        }

        // Check for any site-specific routes
        $sitesService = Craft::$app->getSites();
        foreach ($sitesService->getAllSites() as $site) {
            if (
                isset($routes[$site->handle]) &&
                is_array($routes[$site->handle]) &&
                !isset($routes[$site->handle]['route']) &&
                !isset($routes[$site->handle]['template'])
            ) {
                $siteRoutes = ArrayHelper::remove($routes, $site->handle);

                /** @noinspection PhpUnhandledExceptionInspection */
                if ($site->handle === $sitesService->getCurrentSite()->handle) {
                    // Merge them so that the localized routes come first
                    $routes = array_merge($siteRoutes, $routes);
                }
            }
        }

        return $routes;
    }

    /**
     * Returns the routes defined in the CP.
     *
     * @return array
     */
    public function getDbRoutes(): array
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        $results = (new Query())
            ->select(['uriPattern', 'template'])
            ->from(['{{%routes}}'])
            ->where([
                'or',
                ['siteId' => null],
                ['siteId' => Craft::$app->getSites()->getCurrentSite()->id]
            ])
            ->orderBy(['sortOrder' => SORT_ASC])
            ->all();

        return ArrayHelper::map($results, 'uriPattern', function($result) {
            return ['template' => $result['template']];
        });
    }

    /**
     * Saves a new or existing route.
     *
     * @param array $uriParts The URI as defined by the user. This is an array where each element is either a
     * string or an array containing the name of a subpattern and the subpattern
     * @param string $template The template to route matching requests to
     * @param int|null $siteId The site ID the route should be limited to, if any
     * @param int|null $routeId The route ID, if editing an existing route
     * @return RouteRecord
     * @throws RouteNotFoundException if|null $routeId is invalid
     */
    public function saveRoute(array $uriParts, string $template, int $siteId = null, int $routeId = null): RouteRecord
    {
        // Fire a 'beforeSaveRoute' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_SAVE_ROUTE)) {
            $this->trigger(self::EVENT_BEFORE_SAVE_ROUTE, new RouteEvent([
                'uriParts' => $uriParts,
                'template' => $template,
                'siteId' => $siteId,
                'routeId' => $routeId,
            ]));
        }

        if ($routeId !== null) {
            $routeRecord = RouteRecord::findOne($routeId);

            if (!$routeRecord) {
                throw new RouteNotFoundException("No route exists with the ID '{$routeId}'");
            }
        } else {
            $routeRecord = new RouteRecord();

            // Get the next biggest sort order
            $maxSortOrder = (new Query())
                ->from(['{{%routes}}'])
                ->max('[[sortOrder]]');

            $routeRecord->sortOrder = $maxSortOrder + 1;
        }

        // Compile the URI parts into a regex pattern
        $uriPattern = '';
        $uriParts = array_filter($uriParts);
        $subpatternNameCounts = [];

        foreach ($uriParts as $part) {
            if (is_string($part)) {
                $uriPattern .= $part;
            } else if (is_array($part)) {
                // Is the name a valid handle?
                if (preg_match('/^[a-zA-Z]\w*$/', $part[0])) {
                    $subpatternName = $part[0];
                } else {
                    $subpatternName = 'any';
                }

                // Make sure it's unique
                if (isset($subpatternNameCounts[$subpatternName])) {
                    $subpatternNameCounts[$subpatternName]++;

                    // Append the count to the end of the name
                    $subpatternName .= $subpatternNameCounts[$subpatternName];
                } else {
                    $subpatternNameCounts[$subpatternName] = 1;
                }

                // Add the var as a named subpattern
                $uriPattern .= "<{$subpatternName}:{$part[1]}>";
            }
        }

        $routeRecord->siteId = $siteId;
        $routeRecord->uriParts = Json::encode($uriParts);
        $routeRecord->uriPattern = $uriPattern;
        $routeRecord->template = $template;
        $routeRecord->save();

        // Fire an 'afterSaveRoute' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_SAVE_ROUTE)) {
            $this->trigger(self::EVENT_AFTER_SAVE_ROUTE, new RouteEvent([
                'uriParts' => $uriParts,
                'template' => $template,
                'siteId' => $siteId,
                'routeId' => $routeRecord->id,
            ]));
        }

        return $routeRecord;
    }

    /**
     * Deletes a route by its ID.
     *
     * @param int $routeId
     * @return bool
     */
    public function deleteRouteById(int $routeId): bool
    {
        $routeRecord = RouteRecord::findOne($routeId);

        if (!$routeRecord) {
            return true;
        }

        $uriParts = Json::decodeIfJson($routeRecord->uriParts);

        // Fire a 'beforeDeleteRoute' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_DELETE_ROUTE)) {
            $this->trigger(self::EVENT_BEFORE_DELETE_ROUTE, new RouteEvent([
                'uriParts' => $uriParts,
                'template' => $routeRecord->template,
                'siteId' => $routeRecord->siteId,
                'routeId' => $routeId,
            ]));
        }

        $routeRecord->delete();

        Craft::$app->getDb()->createCommand()
            ->delete('{{%routes}}', ['id' => $routeId])
            ->execute();

        // Fire an 'afterDeleteRoute' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_DELETE_ROUTE)) {
            $this->trigger(self::EVENT_AFTER_DELETE_ROUTE, new RouteEvent([
                'uriParts' => $uriParts,
                'template' => $routeRecord->template,
                'siteId' => $routeRecord->siteId,
                'routeId' => $routeId,
            ]));
        }

        return true;
    }

    /**
     * Updates the route order.
     *
     * @param array $routeIds An array of each of the route IDs, in their new order.
     */
    public function updateRouteOrder(array $routeIds)
    {
        $db = Craft::$app->getDb();

        foreach ($routeIds as $order => $routeId) {
            $db->createCommand()
                ->update(
                    '{{%routes}}',
                    ['sortOrder' => $order + 1],
                    ['id' => $routeId])
                ->execute();
        }
    }
}
