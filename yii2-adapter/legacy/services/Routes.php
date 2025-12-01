<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use Craft;
use craft\events\RouteEvent;
use CraftCms\Cms\Route\Data\Route;
use CraftCms\Cms\Route\Events\DeletingRoute;
use CraftCms\Cms\Route\Events\RouteDeleted;
use CraftCms\Cms\Route\Events\RouteSaved;
use CraftCms\Cms\Route\Events\SavingRoute;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\Sites;
use Illuminate\Support\Facades\Event;
use yii\base\Component;

/**
 * Routes service.
 *
 * An instance of the service is available via [[\craft\base\ApplicationTrait::getRoutes()|`Craft::$app->getRoutes()`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Route\Routes} instead.
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
        foreach (Sites::getAllSites(true) as $site) {
            if (
                isset($routes[$site->handle]) &&
                is_array($routes[$site->handle]) &&
                !isset($routes[$site->handle]['route']) &&
                !isset($routes[$site->handle]['template'])
            ) {
                $siteRoutes = Arr::pull($routes, $site->handle);

                /** @noinspection PhpUnhandledExceptionInspection */
                if ($site->handle === Sites::getCurrentSite()->handle) {
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
        return app(\CraftCms\Cms\Route\Routes::class)
            ->getProjectConfigRoutes()
            ->map(function(Route $route) {
                return [
                    'template' => $route->template,
                    'pattern' => $this->uriPattern($route->uriParts),
                ];
            })
            ->all();
    }

    private function uriPattern(array $uriParts): string
    {
        $uriPattern = '';
        $subpatternNameCounts = [];

        foreach (Arr::whereNotEmpty($uriParts) as $part) {
            if (is_string($part)) {
                $uriPattern .= $part;
                continue;
            }

            if (!is_array($part)) {
                continue;
            }

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

        return $uriPattern;
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
        return app(\CraftCms\Cms\Route\Routes::class)->saveRoute(new Route(
            uid: $routeUid,
            uriParts: $uriParts,
            template: $template,
            siteUid: $siteUid,
        ));
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
        return app(\CraftCms\Cms\Route\Routes::class)->deleteRouteByUid($routeUid);
    }

    /**
     * Updates the route order.
     *
     * @param array $routeUids An array of each of the route UIDs, in their new order.
     */
    public function updateRouteOrder(array $routeUids): void
    {
        app(\CraftCms\Cms\Route\Routes::class)->updateRouteOrder($routeUids);
    }

    public static function registerEvents(): void
    {
        foreach ([
            self::EVENT_BEFORE_SAVE_ROUTE => SavingRoute::class,
            self::EVENT_AFTER_SAVE_ROUTE => RouteSaved::class,
            self::EVENT_BEFORE_DELETE_ROUTE => DeletingRoute::class,
            self::EVENT_AFTER_DELETE_ROUTE => RouteDeleted::class,
        ] as $old => $new) {
            Event::listen($new, function(\CraftCms\Cms\Route\Events\RouteEvent $event) use ($old) {
                if (Craft::$app->getRoutes()->hasEventHandlers($old)) {
                    Craft::$app->getRoutes()->trigger($old, new RouteEvent([
                        'uriParts' => $event->route->uriParts,
                        'template' => $event->route->template,
                        'siteUid' => $event->route->siteUid,
                    ]));
                }
            });
        }
    }
}
