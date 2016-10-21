<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\web\twig\variables;

use Craft;
use craft\app\models\EntryDraft;
use craft\app\models\EntryVersion;
use yii\base\Exception;

/**
 * Class EntryRevisions variable.
 *
 * @author     Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since      3.0
 * @deprecated in 3.0
 */
class EntryRevisions
{
    // Public Methods
    // =========================================================================

    // Drafts
    // -------------------------------------------------------------------------

    /**
     * Returns entry drafts by an entry ID.
     *
     * @param integer $entryId
     * @param string  $siteHandle
     *
     * @return array
     * @throws Exception if $siteHandle is invalid
     */
    public function getDraftsByEntryId($entryId, $siteHandle = null)
    {
        Craft::$app->getDeprecator()->log('craft.entryRevisions.getDraftsByEntryId()', 'craft.entryRevisions.getDraftsByEntryId() has been deprecated. Use craft.app.entryRevisions.getDraftsByEntryId() instead.');

        if ($siteHandle) {
            $site = Craft::$app->getSites()->getSiteByHandle($siteHandle);

            if (!$site) {
                throw new Exception('Invalid site handle: '.$siteHandle);
            }

            $siteId = $site->id;
        } else {
            $siteId = null;
        }

        return Craft::$app->getEntryRevisions()->getDraftsByEntryId($entryId, $siteId);
    }

    /**
     * Returns the drafts of a given entry that are editable by the current user.
     *
     * @param integer $entryId
     * @param string  $siteHandle
     *
     * @return array
     * @throws Exception if $siteHandle is invalid
     */
    public function getEditableDraftsByEntryId($entryId, $siteHandle = null)
    {
        Craft::$app->getDeprecator()->log('craft.entryRevisions.getEditableDraftsByEntryId()', 'craft.entryRevisions.getEditableDraftsByEntryId() has been deprecated. Use craft.app.entryRevisions.getEditableDraftsByEntryId() instead.');

        if ($siteHandle) {
            $site = Craft::$app->getSites()->getSiteByHandle($siteHandle);

            if (!$site) {
                throw new Exception('Invalid site handle: '.$siteHandle);
            }

            $siteId = $site->id;
        } else {
            $siteId = null;
        }

        return Craft::$app->getEntryRevisions()->getEditableDraftsByEntryId($entryId, $siteId);
    }

    /**
     * Returns an entry draft by its offset.
     *
     * @param integer $draftId
     *
     * @return EntryDraft|null
     */
    public function getDraftById($draftId)
    {
        Craft::$app->getDeprecator()->log('craft.entryRevisions.getDraftById()', 'craft.entryRevisions.getDraftById() has been deprecated. Use craft.app.entryRevisions.getDraftById() instead.');

        return Craft::$app->getEntryRevisions()->getDraftById($draftId);
    }

    // Versions
    // -------------------------------------------------------------------------

    /**
     * Returns entry versions by an entry ID.
     *
     * @param integer $entryId
     * @param string  $siteHandle
     *
     * @return array
     * @throws Exception if $siteHandle is invalid
     */
    public function getVersionsByEntryId($entryId, $siteHandle)
    {
        Craft::$app->getDeprecator()->log('craft.entryRevisions.getVersionsByEntryId()', 'craft.entryRevisions.getVersionsByEntryId() has been deprecated. Use craft.app.entryRevisions.getVersionsByEntryId() instead.');

        if ($siteHandle) {
            $site = Craft::$app->getSites()->getSiteByHandle($siteHandle);

            if (!$site) {
                throw new Exception('Invalid site handle: '.$siteHandle);
            }

            $siteId = $site->id;
        } else {
            $siteId = null;
        }

        return Craft::$app->getEntryRevisions()->getVersionsByEntryId($entryId, $siteId, 10);
    }

    /**
     * Returns an entry version by its ID.
     *
     * @param integer $versionId
     *
     * @return EntryVersion|null
     */
    public function getVersionById($versionId)
    {
        Craft::$app->getDeprecator()->log('craft.entryRevisions.getVersionById()', 'craft.entryRevisions.getVersionById() has been deprecated. Use craft.app.entryRevisions.getVersionById() instead.');

        return Craft::$app->getEntryRevisions()->getVersionById($versionId);
    }
}
