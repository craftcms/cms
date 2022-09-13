<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use Craft;
use craft\elements\Entry;
use craft\models\Section;
use craft\models\Site;
use craft\web\Controller;
use yii\web\ForbiddenHttpException;

/**
 * BaseEntriesController is a base class that any entry-related controllers, such as [[EntriesController]] and
 * [[EntryRevisionsController]], extend to share common functionality.
 * It extends [[Controller]], overwriting specific methods as required.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
abstract class BaseEntriesController extends Controller
{
    /**
     * Returns the editable site IDs for a section.
     *
     * @param Section $section
     * @return int[]
     * @throws ForbiddenHttpException
     */
    protected function editableSiteIds(Section $section): array
    {
        if (!Craft::$app->getIsMultiSite()) {
            return [Craft::$app->getSites()->getPrimarySite()->id];
        }

        // Only use the sites that the user has access to
        $sectionSiteIds = array_keys($section->getSiteSettings());
        $editableSiteIds = Craft::$app->getSites()->getEditableSiteIds();
        $siteIds = array_merge(array_intersect($sectionSiteIds, $editableSiteIds));
        if (empty($siteIds)) {
            throw new ForbiddenHttpException('User not permitted to edit content in any sites supported by this section');
        }
        return $siteIds;
    }

    /**
     * Enforces edit site permissions.
     *
     * @param Site $site
     * @throws ForbiddenHttpException
     * @since 3.5.0
     */
    protected function enforceSitePermission(Site $site): void
    {
        if (Craft::$app->getIsMultiSite()) {
            $this->requirePermission('editSite:' . $site->uid);
        }
    }

    /**
     * Enforces all Edit Entry permissions.
     *
     * @param Entry $entry
     * @param bool $duplicate
     * @throws ForbiddenHttpException
     */
    protected function enforceEditEntryPermissions(Entry $entry, bool $duplicate = false): void
    {
        if ($duplicate) {
            $id = $entry->id;
            $entry->id = null;
        }

        $canSave = Craft::$app->getElements()->canSave($entry);

        if ($duplicate) {
            $entry->id = $id;
        }

        if (!$canSave) {
            throw new ForbiddenHttpException('User is not authorized to perform this action.');
        }
    }

    /**
     * Returns the posted `enabledForSite` value, taking the user’s permissions into account.
     *
     * @return bool|bool[]|null
     * @throws ForbiddenHttpException
     * @since 3.4.0
     */
    protected function enabledForSiteValue(): array|bool|null
    {
        $enabledForSite = $this->request->getBodyParam('enabledForSite');
        if (is_array($enabledForSite)) {
            // Make sure they are allowed to edit all of the posted site IDs
            $editableSiteIds = Craft::$app->getSites()->getEditableSiteIds();
            if (array_diff(array_keys($enabledForSite), $editableSiteIds)) {
                throw new ForbiddenHttpException('User not permitted to edit the statuses for all the submitted site IDs');
            }
        }
        return $enabledForSite;
    }
}
