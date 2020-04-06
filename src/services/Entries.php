<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use Craft;
use craft\db\Query;
use craft\elements\Entry;
use yii\base\Component;

/**
 * The Entries service provides APIs for managing entries in Craft.
 * An instance of the Entries service is globally accessible in Craft via [[\craft\base\ApplicationTrait::getEntries()|`Craft::$app->entries`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class Entries extends Component
{
    /**
     * Returns an entry by its ID.
     *
     * ```php
     * $entry = Craft::$app->entries->getEntryById($entryId);
     * ```
     *
     * @param int $entryId The entry’s ID.
     * @param int|null $siteId The site to fetch the entry in. Defaults to the current site.
     * @return Entry|null The entry with the given ID, or `null` if an entry could not be found.
     */
    public function getEntryById(int $entryId, int $siteId = null)
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

        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return Craft::$app->getElements()->getElementById($entryId, Entry::class, $siteId, [
            'structureId' => $structureId,
        ]);
    }
}
