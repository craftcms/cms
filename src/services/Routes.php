<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use Craft;
use craft\events\DeleteSiteEvent;
use craft\events\RouteEvent;
use craft\helpers\ArrayHelper;
use craft\helpers\StringHelper;
use yii\base\Component;

/**
 * Routes service.
 *
 * An instance of the service is available via [[\craft\base\ApplicationTrait::getRoutes()|`Craft::$app->routes`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class Routes extends Component
{
    /**
     * @event RouteEvent The event that is triggered before a route is saved.
     */
    public const EVENT_BEFORE_SAVE_ROUTE = 'beforeSaveRoute';

    /**
     * @event RouteEvent The event that is triggered after a route is saved.
     */
    public const EVENT_AFTER_SAVE_ROUTE = 'afterSaveRoute';

    /**
     * @event RouteEvent The event that is triggered before a route is deleted.
     */
    public const EVENT_BEFORE_DELETE_ROUTE = 'beforeDeleteRoute';

    /**
     * @event RouteEvent The event that is triggered after a route is deleted.
     */
    public const EVENT_AFTER_DELETE_ROUTE = 'afterDeleteRoute';

    /**
     * @var array|null all the routes in project config for current site
     */
    private ?array $_projectConfigRoutes = null;

    /**
     * Returns the routes defined in `config/routes.php`
     *
     * @return array
     */
    public function getConfigFileRoutes(): array
    {
        $path = Craft::$app->getPath()->getConfigPath() . DIRECTORY_SEPARATOR . 'routes.php';

        if (!file_exists($path)) {
            return [];
        }

        $routes = require $path;

        if (!is_array($routes)) {
            return [];
        }

        // Check for any site-specific routes
        $sitesService = Craft::$app->getSites();
        foreach ($sitesService->getAllSites(true) as $site) {
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
     * Returns the routes defined in the project config.
     *
     * @return array
     */
    public function getProjectConfigRoutes(): array
    {
        if (isset($this->_projectConfigRoutes)) {
            return $this->_projectConfigRoutes;
        }

        $this->_projectConfigRoutes = [];

        if (Craft::$app->getConfig()->getGeneral()->headlessMode) {
            return $this->_projectConfigRoutes;
        }

        $routes = Craft::$app->getProjectConfig()->get(ProjectConfig::PATH_ROUTES) ?? [];
        ArrayHelper::multisort($routes, 'sortOrder', SORT_ASC, SORT_NUMERIC);
        $currentSiteUid = Craft::$app->getSites()->getCurrentSite()->uid;
        $this->_projectConfigRoutes = [];

        foreach ($routes as $route) {
            $key = sprintf('pattern:%s', $route['uriPattern']);
            if (
                !isset($this->_projectConfigRoutes[$key]) &&
                (empty($route['siteUid']) || $route['siteUid'] === $currentSiteUid)
            ) {
                $this->_projectConfigRoutes[$key] = [
                    'pattern' => $route['uriPattern'],
                    'template' => $route['template'],
                ];
            }
        }

        return array_values($this->_projectConfigRoutes);
    }

    /**
     * Saves a new or existing route.
     *
     * @param array $uriParts The URI as defined by the user. This is an array where each element is either a
     * string or an array containing the name of a subpattern and the subpattern
     * @param string $template The template to route matching requests to
     * @param string|null $siteUid The site UID the route should be limited to, if any
     * @param string|null $routeUid The route UID, if editing an existing route
     * @return string $routeUid The route UID.
     */
    public function saveRoute(array $uriParts, string $template, ?string $siteUid = null, ?string $routeUid = null): string
    {
        // Fire a 'beforeSaveRoute' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_SAVE_ROUTE)) {
            $this->trigger(self::EVENT_BEFORE_SAVE_ROUTE, new RouteEvent([
                'uriParts' => $uriParts,
                'template' => $template,
                'siteUid' => $siteUid,
            ]));
        }

        $projectConfig = Craft::$app->getProjectConfig();

        if ($routeUid !== null) {
            $sortOrder = $projectConfig->get(ProjectConfig::PATH_ROUTES . '.' . $routeUid . '.sortOrder') ?? $this->_getMaxSortOrder();
        } else {
            $routeUid = StringHelper::UUID();
            $sortOrder = $this->_getMaxSortOrder();
        }

        // Compile the URI parts into a regex pattern
        $uriPattern = '';
        $uriParts = ArrayHelper::filterEmptyStringsFromArray($uriParts);
        $subpatternNameCounts = [];

        foreach ($uriParts as $part) {
            if (is_string($part)) {
                $uriPattern .= $part;
            } elseif (is_array($part)) {
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
                $uriPattern .= "<$subpatternName:$part[1]>";
            }
        }

        $configData = [
            'template' => $template,
            'uriParts' => $uriParts,
            'uriPattern' => $uriPattern,
            'sortOrder' => (int)$sortOrder,
            'siteUid' => $siteUid,
        ];

        $projectConfig->set(ProjectConfig::PATH_ROUTES . '.' . $routeUid, $configData, 'Save route');

        // Fire an 'afterSaveRoute' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_SAVE_ROUTE)) {
            $this->trigger(self::EVENT_AFTER_SAVE_ROUTE, new RouteEvent([
                'uriParts' => $uriParts,
                'template' => $template,
                'siteUid' => $siteUid,
            ]));
        }

        return $routeUid;
    }

    /**
     * Deletes a route by its ID.
     *
     * @param string $routeUid
     * @return bool
     * @since 3.1.0
     */
    public function deleteRouteByUid(string $routeUid): bool
    {
        $route = Craft::$app->getProjectConfig()->get(ProjectConfig::PATH_ROUTES . '.' . $routeUid);

        if ($route) {
            // Fire a 'beforeDeleteRoute' event
            if ($this->hasEventHandlers(self::EVENT_BEFORE_DELETE_ROUTE)) {
                $this->trigger(self::EVENT_BEFORE_DELETE_ROUTE, new RouteEvent([
                    'uriParts' => $route['uriParts'],
                    'template' => $route['template'],
                    'siteUid' => $route['siteUid'],
                ]));
            }

            Craft::$app->getProjectConfig()->remove(ProjectConfig::PATH_ROUTES . '.' . $routeUid, "Delete route");

            // Fire an 'afterDeleteRoute' event
            if ($this->hasEventHandlers(self::EVENT_AFTER_DELETE_ROUTE)) {
                $this->trigger(self::EVENT_AFTER_DELETE_ROUTE, new RouteEvent([
                    'uriParts' => $route['uriParts'],
                    'template' => $route['template'],
                    'siteUid' => $route['siteUid'],
                ]));
            }
        }

        return true;
    }

    /**
     * Handle a deleted site when it affects existing routes
     *
     * @param DeleteSiteEvent $event
     */
    public function handleDeletedSite(DeleteSiteEvent $event): void
    {
        $projectConfig = Craft::$app->getProjectConfig();
        $routes = $projectConfig->get(ProjectConfig::PATH_ROUTES) ?? [];

        foreach ($routes as $routeUid => $route) {
            if ($route['siteUid'] === $event->site->uid) {
                $projectConfig->remove(ProjectConfig::PATH_ROUTES . '.' . $routeUid, 'Remove routes that belong to a site being deleted');
            }
        }
    }

    /**
     * Updates the route order.
     *
     * @param array $routeUids An array of each of the route UIDs, in their new order.
     */
    public function updateRouteOrder(array $routeUids): void
    {
        foreach ($routeUids as $order => $routeUid) {
            Craft::$app->getProjectConfig()->set(ProjectConfig::PATH_ROUTES . '.' . $routeUid . '.sortOrder', $order + 1, 'Reorder routes');
        }
    }

    /**
     * Return the current max sort order for routes.
     *
     * @return int
     */
    private function _getMaxSortOrder(): int
    {
        $routes = Craft::$app->getProjectConfig()->get(ProjectConfig::PATH_ROUTES) ?? [];
        $max = 0;

        foreach ($routes as $route) {
            $max = max($max, $route['sortOrder']);
        }

        return (int)$max + 1;
    }
}
