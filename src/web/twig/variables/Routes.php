<?php
/**
 * @link      http://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license
 */

namespace craft\app\web\twig\variables;

use craft\app\db\Query;
use craft\app\helpers\Html;
use craft\app\helpers\Json;

/**
 * Route functions.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class Routes
{
    // Public Methods
    // =========================================================================

    /**
     * Returns the routes defined in the CP.
     *
     * @return array
     */
    public function getDbRoutes()
    {
        $routes = [];

        $results = (new Query())
            ->select(['id', 'locale', 'urlParts', 'template'])
            ->from('{{%routes}}')
            ->orderBy('sortOrder')
            ->all();

        foreach ($results as $result) {
            $urlDisplayHtml = '';
            $urlParts = Json::decode($result['urlParts']);

            foreach ($urlParts as $part) {
                if (is_string($part)) {
                    $urlDisplayHtml .= $part;
                } else {
                    $urlDisplayHtml .= Html::encodeParams('<span class="token" data-name="{name}" data-value="{value}"><span>{name}</span></span>',
                        [
                            'name' => $part[0],
                            'value' => $part[1]
                        ]);
                }
            }

            $routes[] = [
                'id' => $result['id'],
                'locale' => $result['locale'],
                'urlDisplayHtml' => $urlDisplayHtml,
                'template' => $result['template']
            ];
        }

        return $routes;
    }
}
