<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\web\twig\variables;

use Craft;
use craft\app\elements\GlobalSet;
use yii\base\Exception;

/**
 * Globals functions.
 *
 * @author     Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since      3.0
 * @deprecated in 3.0
 */
class Globals
{
    // Public Methods
    // =========================================================================

    /**
     * Returns all global sets.
     *
     * @param string|null $indexBy
     *
     * @return array
     */
    public function getAllSets($indexBy = null)
    {
        Craft::$app->getDeprecator()->log('craft.globals.getAllSets()', 'craft.globals.getAllSets() has been deprecated. Use craft.app.globals.getAllSets() instead.');

        return Craft::$app->getGlobals()->getAllSets($indexBy);
    }

    /**
     * Returns all global sets that are editable by the current user.
     *
     * @param string|null $indexBy
     *
     * @return array
     */
    public function getEditableSets($indexBy = null)
    {
        Craft::$app->getDeprecator()->log('craft.globals.getEditableSets()', 'craft.globals.getEditableSets() has been deprecated. Use craft.app.globals.getEditableSets() instead.');

        return Craft::$app->getGlobals()->getEditableSets($indexBy);
    }

    /**
     * Returns the total number of global sets.
     *
     * @return integer
     */
    public function getTotalSets()
    {
        Craft::$app->getDeprecator()->log('craft.globals.getTotalSets()', 'craft.globals.getTotalSets() has been deprecated. Use craft.app.globals.totalSets instead.');

        return Craft::$app->getGlobals()->getTotalSets();
    }

    /**
     * Returns the total number of global sets that are editable by the current user.
     *
     * @return integer
     */
    public function getTotalEditableSets()
    {
        Craft::$app->getDeprecator()->log('craft.globals.getTotalEditableSets()', 'craft.globals.getTotalEditableSets() has been deprecated. Use craft.app.globals.totalEditableSets instead.');

        return Craft::$app->getGlobals()->getTotalEditableSets();
    }

    /**
     * Returns a global set by its ID.
     *
     * @param integer     $globalSetId
     * @param string|null $siteHandle
     *
     * @return GlobalSet|null
     * @throws Exception if $siteHandle is invlaid
     */
    public function getSetById($globalSetId, $siteHandle = null)
    {
        Craft::$app->getDeprecator()->log('craft.globals.getSetById()', 'craft.globals.getSetById() has been deprecated. Use craft.app.globals.getSetById() instead.');

        if ($siteHandle) {
            $site = Craft::$app->getSites()->getSiteByHandle($siteHandle);

            if (!$site) {
                throw new Exception('Invalid site handle: '.$siteHandle);
            }

            $siteId = $site->id;
        } else {
            $siteId = null;
        }

        return Craft::$app->getGlobals()->getSetById($globalSetId, $siteId);
    }

    /**
     * Returns a global set by its handle.
     *
     * @param string      $globalSetHandle
     * @param string|null $siteHandle
     *
     * @return GlobalSet|null
     * @throws Exception if $siteHandle is invalid
     */
    public function getSetByHandle($globalSetHandle, $siteHandle = null)
    {
        Craft::$app->getDeprecator()->log('craft.globals.getSetByHandle()', 'craft.globals.getSetByHandle() has been deprecated. Use craft.app.globals.getSetByHandle() instead.');

        if ($siteHandle) {
            $site = Craft::$app->getSites()->getSiteByHandle($siteHandle);

            if (!$site) {
                throw new Exception('Invalid site handle: '.$siteHandle);
            }

            $siteId = $site->id;
        } else {
            $siteId = null;
        }

        return Craft::$app->getGlobals()->getSetByHandle($globalSetHandle, $siteId);
    }
}
