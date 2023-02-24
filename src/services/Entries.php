<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use Craft;
use craft\db\Query;
use craft\db\Table;
use craft\elements\Entry;
use craft\helpers\ArrayHelper;
use craft\models\Section;
use yii\base\Component;

/**
 * The Entries service provides APIs for managing entries in Craft.
 *
 * An instance of the service is available via [[\craft\base\ApplicationTrait::getEntries()|`Craft::$app->entries`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class Entries extends Component
{
    /**
     * @var array<int,array<string,Entry|false>>
     */
    private array $_singleEntries = [];

    /**
     * Returns an entry by its ID.
     *
     * ```php
     * $entry = Craft::$app->entries->getEntryById($entryId);
     * ```
     *
     * @param int $entryId The entry’s ID.
     * @param int|string|int[]|null $siteId The site(s) to fetch the entry in.
     * Defaults to the current site.
     * @param array $criteria
     * @return Entry|null The entry with the given ID, or `null` if an entry could not be found.
     */
    public function getEntryById(int $entryId, array|int|string $siteId = null, array $criteria = []): ?Entry
    {
        if (!$entryId) {
            return null;
        }

        // Get the structure ID
        if (!isset($criteria['structureId'])) {
            $criteria['structureId'] = (new Query())
                ->select(['sections.structureId'])
                ->from(['entries' => Table::ENTRIES])
                ->innerJoin(['sections' => Table::SECTIONS], '[[sections.id]] = [[entries.sectionId]]')
                ->where(['entries.id' => $entryId])
                ->scalar();
        }

        return Craft::$app->getElements()->getElementById($entryId, Entry::class, $siteId, $criteria);
    }

    /**
     * Returns an array of Single section entries which match a given list of section handles.
     *
     * @param string[] $handles
     * @return array<string,Entry>
     * @since 4.4.0
     */
    public function getSingleEntriesByHandle(array $handles): array
    {
        $entries = [];
        $siteId = Craft::$app->getSites()->getCurrentSite()->id;
        $missingEntries = [];

        if (!isset($this->_singleEntries[$siteId])) {
            $this->_singleEntries[$siteId] = [];
        }

        foreach ($handles as $handle) {
            if (isset($this->_singleEntries[$siteId][$handle])) {
                if ($this->_singleEntries[$siteId][$handle] !== false) {
                    $entries[$handle] = $this->_singleEntries[$siteId][$handle];
                }
            } else {
                $missingEntries[] = $handle;
            }
        }

        if (!empty($missingEntries)) {
            /** @var array<string,Section> $singleSections */
            $singleSections = ArrayHelper::index(
                Craft::$app->getSections()->getSectionsByType(Section::TYPE_SINGLE),
                fn(Section $section) => $section->handle,
            );
            $fetchSectionIds = [];
            $fetchSectionHandles = [];
            foreach ($missingEntries as $handle) {
                if (isset($singleSections[$handle])) {
                    $fetchSectionIds[] = $singleSections[$handle]->id;
                    $fetchSectionHandles[] = $handle;
                } else {
                    $this->_singleEntries[$siteId][$handle] = false;
                }
            }
            if (!empty($fetchSectionIds)) {
                $fetchedEntries = Entry::find()
                    ->sectionId($fetchSectionIds)
                    ->siteId($siteId)
                    ->all();
                /** @var array<string,Entry> $fetchedEntries */
                $fetchedEntries = ArrayHelper::index($fetchedEntries, fn(Entry $entry) => $entry->getSection()->handle);
                foreach ($fetchSectionHandles as $handle) {
                    if (isset($fetchedEntries[$handle])) {
                        $this->_singleEntries[$siteId][$handle] = $fetchedEntries[$handle];
                        $entries[$handle] = $fetchedEntries[$handle];
                    } else {
                        $this->_singleEntries[$siteId][$handle] = false;
                    }
                }
            }
        }

        return $entries;
    }
}
