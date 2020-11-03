<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\twig\variables;

use Craft;
use craft\helpers\Html;
use craft\services\Routes as RoutesService;

/**
 * Route functions.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class Routes
{
    /**
     * Returns the routes defined in the control panel.
     *
     * @return array
     */
    public function getProjectConfigRoutes(): array
    {
        $routes = [];
        $sortOrders = [];

        $results = Craft::$app->getProjectConfig()->get(RoutesService::CONFIG_ROUTES_KEY) ?? [];

        foreach ($results as $routeUid => $route) {
            $uriDisplayHtml = '';

            if (!empty($route['uriParts'])) {
                foreach ($route['uriParts'] as $part) {
                    if (is_string($part)) {
                        $uriDisplayHtml .= Html::encode($part);
                    } else {
                        $uriDisplayHtml .= Html::encodeParams('<span class="token" data-name="{name}" data-value="{value}"><span>{name}</span></span>',
                            [
                                'name' => $part[0],
                                'value' => $part[1]
                            ]);
                    }
                }
            }

            $routes[] = [
                'uid' => $routeUid,
                'siteUid' => $route['siteUid'],
                'uriDisplayHtml' => $uriDisplayHtml,
                'template' => $route['template']
            ];
            $sortOrders[] = $route['sortOrder'];
        }

        array_multisort($sortOrders, SORT_ASC, SORT_NUMERIC, $routes);
        return $routes;
    }
}
