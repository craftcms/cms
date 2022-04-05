<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\twig\variables;

use Craft;
use craft\elements\GlobalSet;
use craft\helpers\ArrayHelper;
use yii\base\Exception;

/**
 * Globals functions.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 * @deprecated in 3.0.0
 */
class Globals
{
    /**
     * Returns all global sets.
     *
     * @param string|null $indexBy
     * @return array
     */
    public function getAllSets(string $indexBy = null): array
    {
        Craft::$app->getDeprecator()->log('craft.globals.getAllSets()', '`craft.globals.getAllSets()` has been deprecated. Use `craft.app.globals.allSets` instead.');

        $globalSets = Craft::$app->getGlobals()->getAllSets();

        return $indexBy ? ArrayHelper::index($globalSets, $indexBy) : $globalSets;
    }

    /**
     * Returns all global sets that are editable by the current user.
     *
     * @param string|null $indexBy
     * @return array
     */
    public function getEditableSets(string $indexBy = null): array
    {
        Craft::$app->getDeprecator()->log('craft.globals.getEditableSets()', '`craft.globals.getEditableSets()` has been deprecated. Use `craft.app.globals.editableSets` instead.');

        $globalSets = Craft::$app->getGlobals()->getEditableSets();

        return $indexBy ? ArrayHelper::index($globalSets, $indexBy) : $globalSets;
    }

    /**
     * Returns the total number of global sets.
     *
     * @return int
     */
    public function getTotalSets(): int
    {
        Craft::$app->getDeprecator()->log('craft.globals.getTotalSets()', '`craft.globals.getTotalSets()` has been deprecated. Use `craft.app.globals.totalSets` instead.');

        return Craft::$app->getGlobals()->getTotalSets();
    }

    /**
     * Returns the total number of global sets that are editable by the current user.
     *
     * @return int
     */
    public function getTotalEditableSets(): int
    {
        Craft::$app->getDeprecator()->log('craft.globals.getTotalEditableSets()', '`craft.globals.getTotalEditableSets()` has been deprecated. Use `craft.app.globals.totalEditableSets` instead.');

        return Craft::$app->getGlobals()->getTotalEditableSets();
    }

    /**
     * Returns a global set by its ID.
     *
     * @param int $globalSetId
     * @param string|null $siteHandle
     * @return GlobalSet|null
     * @throws Exception if|null $siteHandle is invlaid
     */
    public function getSetById(int $globalSetId, string $siteHandle = null)
    {
        Craft::$app->getDeprecator()->log('craft.globals.getSetById()', '`craft.globals.getSetById()` has been deprecated. Use `craft.app.globals.getSetById()` instead.');

        if ($siteHandle !== null) {
            $site = Craft::$app->getSites()->getSiteByHandle($siteHandle);

            if (!$site) {
                throw new Exception('Invalid site handle: ' . $siteHandle);
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
     * @param string $globalSetHandle
     * @param string|null $siteHandle
     * @return GlobalSet|null
     * @throws Exception if|null $siteHandle is invalid
     */
    public function getSetByHandle(string $globalSetHandle, string $siteHandle = null)
    {
        Craft::$app->getDeprecator()->log('craft.globals.getSetByHandle()', '`craft.globals.getSetByHandle()` has been deprecated. Use `craft.app.globals.getSetByHandle()` instead.');

        if ($siteHandle !== null) {
            $site = Craft::$app->getSites()->getSiteByHandle($siteHandle);

            if (!$site) {
                throw new Exception('Invalid site handle: ' . $siteHandle);
            }

            $siteId = $site->id;
        } else {
            $siteId = null;
        }

        return Craft::$app->getGlobals()->getSetByHandle($globalSetHandle, $siteId);
    }
}
