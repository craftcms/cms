<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\web\twig\variables;

/**
 * Update functions.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class Updates
{
    // Public Methods
    // =========================================================================

    /**
     * Returns whether the update info is cached.
     *
     * @return boolean
     */
    public function isUpdateInfoCached()
    {
        return \Craft::$app->getUpdates()->getIsUpdateInfoCached();
    }

    /**
     * Returns whether a critical update is available.
     *
     * @return boolean
     */
    public function isCriticalUpdateAvailable()
    {
        return \Craft::$app->getUpdates()->getIsCriticalUpdateAvailable();
    }

    /**
     * Returns the folders that need to be set to writable.
     *
     * @return array
     */
    public function getUnwritableFolders()
    {
        return \Craft::$app->getUpdates()->getUnwritableFolders();
    }

    /**
     * @param boolean $forceRefresh
     *
     * @return mixed
     */
    public function getUpdates($forceRefresh = false)
    {
        return \Craft::$app->getUpdates()->getUpdates($forceRefresh);
    }

    /**
     * @return string|null
     */
    public function getManualUpdateDisplayName()
    {
        return $this->_getManualUpdateInfo('name');
    }

    /**
     * @return string|null
     */
    public function getManualUpdateHandle()
    {
        return $this->_getManualUpdateInfo('handle');
    }

    // Private Methods
    // =========================================================================

    /**
     * @param string $type
     *
     * @return string|null
     */
    private function _getManualUpdateInfo($type)
    {
        if (\Craft::$app->getUpdates()->getIsCraftDbMigrationNeeded()) {
            return 'Craft';
        }

        $plugins = \Craft::$app->getUpdates()->getPluginsThatNeedDbUpdate();

        if (!empty($plugins) && isset($plugins[0])) {
            if ($type == 'name') {
                return $plugins[0]->name;
            }

            return $plugins[0]->getHandle();
        }

        return null;
    }
}
