<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\twig\variables;

use craft\db\Query;
use craft\helpers\Html;
use craft\helpers\Json;

/**
 * Route functions.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
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
    public function getDbRoutes(): array
    {
        $routes = [];

        $results = (new Query())
            ->select(['id', 'siteId', 'uriParts', 'template'])
            ->from(['{{%routes}}'])
            ->orderBy(['sortOrder' => SORT_ASC])
            ->all();

        foreach ($results as $result) {
            $uriDisplayHtml = '';
            $uriParts = Json::decode($result['uriParts']);

            foreach ($uriParts as $part) {
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

            $routes[] = [
                'id' => $result['id'],
                'siteId' => $result['siteId'],
                'uriDisplayHtml' => $uriDisplayHtml,
                'template' => $result['template']
            ];
        }

        return $routes;
    }
}
