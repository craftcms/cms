<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\services;

use Craft;
use craft\app\db\Query;
use craft\app\errors\RouteNotFoundException;
use craft\app\events\RouteEvent;
use craft\app\helpers\Io;
use craft\app\helpers\Json;
use craft\app\records\Route as RouteRecord;
use yii\base\Component;

/**
 * Class Routes service.
 *
 * An instance of the Routes service is globally accessible in Craft via [[Application::routes `Craft::$app->getRoutes()`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
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
     * Returns the routes defined in craft/config/routes.php
     *
     * @return array
     */
    public function getConfigFileRoutes()
    {
        $path = Craft::$app->getPath()->getConfigPath().'/routes.php';

        if (Io::fileExists($path)) {
            $routes = require_once($path);

            if (is_array($routes)) {
                // Check for any site-specific routes
                $siteHandle = Craft::$app->getSites()->currentSite->handle;

                if (
                    isset($routes[$siteHandle]) &&
                    is_array($routes[$siteHandle]) &&
                    !isset($routes[$siteHandle]['route']) &&
                    !isset($routes[$siteHandle]['template'])
                ) {
                    $localizedRoutes = $routes[$siteHandle];
                    unset($routes[$siteHandle]);

                    // Merge them so that the localized routes come first
                    $routes = array_merge($localizedRoutes, $routes);
                }

                return $routes;
            }
        }

        return [];
    }

    /**
     * Returns the routes defined in the CP.
     *
     * @return array
     */
    public function getDbRoutes()
    {
        $results = (new Query())
            ->select(['uriPattern', 'template'])
            ->from('{{%routes}}')
            ->where(['or', 'siteId is null', 'siteId = :siteId'],
                [':siteId' => Craft::$app->getSites()->currentSite->id])
            ->orderBy('sortOrder')
            ->all();

        if ($results) {
            $routes = [];

            foreach ($results as $result) {
                $routes[$result['uriPattern']] = ['template' => $result['template']];
            }

            return $routes;
        }

        return [];
    }

    /**
     * Saves a new or existing route.
     *
     * @param array        $uriParts The URI as defined by the user. This is an array where each element is either a
     *                               string or an array containing the name of a subpattern and the subpattern
     * @param string       $template The template to route matching requests to
     * @param integer|null $siteId   The site ID the route should be limited to, if any
     * @param integer|null $routeId  The route ID, if editing an existing route
     *
     * @return RouteRecord
     * @throws RouteNotFoundException if $routeId is invalid
     */
    public function saveRoute($uriParts, $template, $siteId = null, $routeId = null)
    {
        // Fire a 'beforeSaveRoute' event
        $this->trigger(self::EVENT_BEFORE_SAVE_ROUTE, new RouteEvent([
            'uriParts' => $uriParts,
            'template' => $template,
            'siteId' => $siteId,
            'routeId' => $routeId,
        ]));

        if ($routeId !== null) {
            $routeRecord = RouteRecord::findOne($routeId);

            if (!$routeRecord) {
                throw new RouteNotFoundException("No route exists with the ID '{$routeId}'");
            }
        } else {
            $routeRecord = new RouteRecord();

            // Get the next biggest sort order
            $maxSortOrder = (new Query())
                ->from('{{%routes}}')
                ->max('sortOrder');

            $routeRecord->sortOrder = $maxSortOrder + 1;
        }

        // Compile the URI parts into a regex pattern
        $uriPattern = '';
        $uriParts = array_filter($uriParts);
        $subpatternNameCounts = [];

        foreach ($uriParts as $part) {
            if (is_string($part)) {
                // Escape any special regex characters
                $uriPattern .= $this->_escapeRegexChars($part);
            } else if (is_array($part)) {
                // Is the name a valid handle?
                if (preg_match('/^[a-zA-Z][a-zA-Z0-9_]*$/', $part[0])) {
                    $subpatternName = $part[0];

                    // Make sure it's unique
                    if (isset($subpatternNameCounts[$subpatternName])) {
                        $subpatternNameCounts[$subpatternName]++;

                        // Append the count to the end of the name
                        $subpatternName .= $subpatternNameCounts[$subpatternName];
                    } else {
                        $subpatternNameCounts[$subpatternName] = 1;
                    }

                    // Add the var as a named subpattern
                    $uriPattern .= '(?P<'.preg_quote($subpatternName).'>'.$part[1].')';
                } else {
                    // Just match it
                    $uriPattern .= '('.$part[1].')';
                }
            }
        }

        $routeRecord->siteId = $siteId;
        $routeRecord->uriParts = Json::encode($uriParts);
        $routeRecord->uriPattern = $uriPattern;
        $routeRecord->template = $template;
        $routeRecord->save();

        // Fire an 'afterSaveRoute' event
        $this->trigger(self::EVENT_AFTER_SAVE_ROUTE, new RouteEvent([
            'uriParts' => $uriParts,
            'template' => $template,
            'siteId' => $siteId,
            'routeId' => $routeRecord->id,
        ]));

        return $routeRecord;
    }

    /**
     * Deletes a route by its ID.
     *
     * @param integer $routeId
     *
     * @return boolean
     */
    public function deleteRouteById($routeId)
    {
        $routeRecord = RouteRecord::findOne($routeId);

        if (!$routeRecord) {
            return true;
        }

        $uriParts = Json::decodeIfJson($routeRecord->uriParts);

        // Fire a 'beforeDeleteRoute' event
        $this->trigger(self::EVENT_BEFORE_DELETE_ROUTE, new RouteEvent([
            'uriParts' => $uriParts,
            'template' => $routeRecord->template,
            'siteId' => $routeRecord->siteId,
            'routeId' => $routeId,
        ]));

        $routeRecord->delete();

        Craft::$app->getDb()->createCommand()
            ->delete('{{%routes}}', ['id' => $routeId])
            ->execute();

        // Fire an 'afterDeleteRoute' event
        $this->trigger(self::EVENT_AFTER_DELETE_ROUTE, new RouteEvent([
            'uriParts' => $uriParts,
            'template' => $routeRecord->template,
            'siteId' => $routeRecord->siteId,
            'routeId' => $routeId,
        ]));

        return true;
    }

    /**
     * Updates the route order.
     *
     * @param array $routeIds An array of each of the route IDs, in their new order.
     *
     * @return void
     */
    public function updateRouteOrder($routeIds)
    {
        foreach ($routeIds as $order => $routeId) {
            $data = ['sortOrder' => $order + 1];
            $condition = ['id' => $routeId];

            Craft::$app->getDb()->createCommand()
                ->update('{{%routes}}', $data, $condition)
                ->execute();
        }
    }

    /**
     * @param $string
     *
     * @return mixed
     */
    private function _escapeRegexChars($string)
    {
        $charsToEscape = str_split("\\/^$.,{}[]()|<>:*+-=");
        $escapedChars = [];

        foreach ($charsToEscape as $char) {
            $escapedChars[] = "\\".$char;
        }

        return str_replace($charsToEscape, $escapedChars, $string);
    }
}
