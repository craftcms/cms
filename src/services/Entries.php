<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\services;

use Craft;
use craft\app\db\Query;
use craft\app\elements\Entry;
use yii\base\Component;

/**
 * The Entries service provides APIs for managing entries in Craft.
 *
 * An instance of the Entries service is globally accessible in Craft via [[Application::entries `Craft::$app->getEntries()`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class Entries extends Component
{
    // Public Methods
    // =========================================================================

    /**
     * Returns an entry by its ID.
     *
     * ```php
     * $entry = Craft::$app->getEntries()->getEntryById($entryId);
     * ```
     *
     * @param integer $entryId The entry’s ID.
     * @param integer $siteId  The site to fetch the entry in. Defaults to the current site.
     *
     * @return Entry|null The entry with the given ID, or `null` if an entry could not be found.
     */
    public function getEntryById($entryId, $siteId = null)
    {
        if (!$entryId) {
            return null;
        }

        // Get the structure ID
        $structureId = (new Query())
            ->select(['sections.structureId'])
            ->from(['{{%entries}} entries'])
            ->innerJoin('{{%sections}} sections', '[[sections.id]] = [[entries.sectionId]]')
            ->where(['entries.id' => $entryId])
            ->scalar();

        return Entry::find()
            ->id($entryId)
            ->structureId($structureId)
            ->siteId($siteId)
            ->status(null)
            ->enabledForSite(false)
            ->one();
    }
}
