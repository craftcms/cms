<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\services;

use Craft;
use yii\base\Component;

/**
 * The Utilities service provides APIs for managing utilties.
 *
 * An instance of the Utilities service is globally accessible in Craft via [[Application::utilities `Craft::$app->getUtilities()`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class Utilities extends Component
{
    // Public Methods
    // =========================================================================

    /**
     * Returns nav items for Utilities the user has access to
     *
     * @return array
     */
    public function getNavItems()
    {
        $items = [];

        $allItems = $this->getAllNavItems();

        foreach ($allItems as $handle => $item) {
            if (Craft::$app->getUser()->checkPermission('utility:'.$handle)) {
                $items[$handle] = $item;
            }
        }

        return $items;
    }

    /**
     * Returns all nav items for Utilities
     *
     * @return array
     */
    public function getAllNavItems()
    {
        $items = [];

        $items['systemReport'] = [
            'label' => "System Report",
            'url' => 'utilities/system-report',
            'icon' => 'section',
        ];

        $items['phpInfo'] = [
            'label' => "PHP Info",
            'url' => 'utilities/php-info',
            'icon' => 'info',
        ];

        $items['deprecationErrors'] = [
            'label' => "Deprecation Errors",
            'url' => 'utilities/deprecation-errors',
            'icon' => 'alert',
            'badgeCount' => Craft::$app->deprecator->getTotalLogs(),
        ];

        $volumes = Craft::$app->getVolumes()->getAllVolumes();

        if (count($volumes) > 0) {
            $items['assetIndex'] = [
                'label' => "Update Asset Indexes",
                'url' => 'utilities/asset-index',
                'icon' => 'assets'
            ];
        }

        $items['clearCaches'] = [
            'label' => "Clear Caches",
            'url' => 'utilities/clear-caches',
            'icon' => 'trash'
        ];

        $items['dbBackup'] = [
            'label' => "Backup Database",
            'url' => 'utilities/db-backup',
            'icon' => 'database'
        ];

        $items['findAndReplace'] = [
            'label' => "Find and Replace",
            'url' => 'utilities/find-and-replace',
            'icon' => 'wand'
        ];

        $items['searchIndex'] = [
            'label' => "Rebuild Search Index",
            'url' => 'utilities/search-index',
            'icon' => 'search'
        ];

        return $items;
    }
}
