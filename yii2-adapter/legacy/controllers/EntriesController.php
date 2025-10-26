<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use Craft;
use craft\elements\Entry;
use craft\errors\InvalidElementException;
use craft\errors\UnsupportedSiteException;
use craft\helpers\Cp;
use craft\helpers\DateTimeHelper;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Section\Enums\SectionType;
use CraftCms\Cms\Support\Facades\Entries;
use CraftCms\Cms\Support\Facades\Sections;
use CraftCms\Cms\Support\Html;
use Exception;
use Illuminate\Support\Facades\DB;
use Tpetry\QueryExpressions\Language\Alias;
use yii\web\BadRequestHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use function CraftCms\Cms\t;

/**
 * The EntriesController class is a controller that handles various entry related tasks such as retrieving, saving,
 * swapping between entry types, and deleting entries.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class EntriesController extends BaseEntriesController
{
    /**
     * Get sections that we can move selected entries to and return the list html for the modal.
     *
     * @return Response
     * @throws BadRequestHttpException
     * @since 5.3.0
     */
    public function actionMoveToSectionModalData(): Response
    {
        $this->requireCpRequest();

        $entryIds = $this->request->getRequiredParam('entryIds');
        $siteId = $this->request->getRequiredParam('siteId');
        $currentSectionUid = $this->request->getRequiredParam('currentSectionUid');

        // get entry types by entry IDs
        $entryTypes = DB::table(Table::ENTRYTYPES, 'et')
            ->distinct()
            ->leftJoin(new Alias(Table::ENTRIES, 'e'), 'e.typeId', 'et.id')
            ->whereIn('e.id', $entryIds)
            ->pluck('et.id')
            ->all();

        $user = Craft::$app->getUser()->getIdentity();

        // filter all sections to those that have all the entry types we just got
        $compatibleSections = Sections::getEditableSections()
            ->filter(function(\CraftCms\Cms\Section\Data\Section $section) use ($entryTypes, $siteId, $currentSectionUid, $user) {
                // don't allow moving to a single section
                if ($section->type === SectionType::Single) {
                    return false;
                }

                // limit to the sections available for the site we're doing this for
                if (!isset($section->getSiteSettings()[$siteId])) {
                    return false;
                }

                // exclude section we started this move from
                if ($currentSectionUid !== null && $section->uid === $currentSectionUid) {
                    return false;
                }

                // ensure person can save entries in the section we're moving to
                if (!$user->can("saveEntries:$section->uid")) {
                    return false;
                }


                $sectionEntryTypes = array_map(fn($et) => $et->id, $section->getEntryTypes());

                return !empty(array_intersect($entryTypes, $sectionEntryTypes));
            })
            ->sortBy(fn(\CraftCms\Cms\Section\Data\Section $section) => $section->getUiLabel())
            ->all();

        if (empty($compatibleSections)) {
            $listHtml = Html::tag(
                'p',
                t('Couldn’t find any sections that all selected elements could be moved to.'),
                ['class' => 'zilch']
            );
        } else {
            $listHtml = '';
            foreach ($compatibleSections as $section) {
                $listHtml .= Cp::chipHtml($section, [
                    'selectable' => true,
                    'class' => 'fullwidth',
                ]);
            }
        }

        return $this->asJson(['listHtml' => $listHtml]);
    }

    /**
     * Move entries to a new section.
     *
     * @return Response
     * @throws BadRequestHttpException
     * @since 5.3.0
     */
    public function actionMoveToSection(): Response
    {
        $this->requireCpRequest();

        $sectionId = $this->request->getRequiredParam('sectionId');
        $section = Sections::getSectionById($sectionId);
        if (!$section) {
            throw new BadRequestHttpException('Cannot find the section to move the entries to.');
        }

        $entryIds = $this->request->getRequiredParam('entryIds');
        if (empty($entryIds)) {
            throw new BadRequestHttpException('entryIds cannot be empty.');
        }
        $entries = Entry::find()
            ->id($entryIds)
            ->status(null)
            ->drafts(null)
            ->site('*')
            ->unique()
            ->all();
        if (empty($entries)) {
            throw new BadRequestHttpException('Cannot find the entries to move to the new section.');
        }

        $errors = [];
        foreach ($entries as $entry) {
            try {
                Entries::moveEntryToSection($entry, $section);
            } catch (Exception|InvalidElementException|UnsupportedSiteException $e) {
                Craft::error('Could not delete move entry to a different section: ' . $e->getMessage(), __METHOD__);
                $errors[] = $e->getMessage();
            }
        }

        if (!empty($errors)) {
            if (count($errors) === count($entries)) {
                return $this->asFailure(t(
                    'Couldn’t move entries to the “{name}” section.',
                    ['name' => $section->name]
                ));
            }

            return $this->asSuccess(t(
                'Some entries have been moved to the “{name}” section.',
                ['name' => $section->name]
            ));
        }

        return $this->asSuccess(t(
            'Entries have been moved to the “{name}” section.',
            ['name' => $section->name]
        ));
    }

    /**
     * Fetches or creates an Entry.
     *
     * @return Entry
     * @throws NotFoundHttpException if the requested entry cannot be found
     */
    private function _getEntryModel(): Entry
    {
        $entryId = $this->request->getBodyParam('canonicalId') ?? $this->request->getBodyParam('sourceId') ?? $this->request->getBodyParam('entryId');
        $siteId = $this->request->getBodyParam('siteId');

        if ($entryId) {
            // Is this a provisional draft?
            $provisional = $this->request->getBodyParam('provisional');
            if ($provisional) {
                /** @var Entry|null $entry */
                $entry = Entry::find()
                    ->provisionalDrafts()
                    ->draftOf($entryId)
                    ->draftCreator(static::currentUser())
                    ->siteId($siteId)
                    ->status(null)
                    ->one();

                if ($entry) {
                    return $entry;
                }
            }

            $entry = Entries::getEntryById($entryId, $siteId);

            if ($entry) {
                return $entry;
            }

            throw new NotFoundHttpException('Entry not found');
        }

        // Pass the config into the constructor so they're in place for ensureBehaviors()
        return new Entry(array_filter([
            'sectionId' => $this->request->getRequiredBodyParam('sectionId'),
            'siteId' => $siteId,
        ]));
    }

    /**
     * Populates an Entry with post data.
     *
     * @param Entry $entry
     */
    private function _populateEntryModel(Entry $entry): void
    {
        // Set the entry attributes, defaulting to the existing values for whatever is missing from the post data
        $entry->typeId = $this->request->getBodyParam('typeId', $entry->typeId);
        $entry->slug = $this->request->getBodyParam('slug', $entry->slug);
        if (($postDate = $this->request->getBodyParam('postDate')) !== null) {
            $entry->postDate = DateTimeHelper::toDateTime($postDate) ?: null;
        }
        if (($expiryDate = $this->request->getBodyParam('expiryDate')) !== null) {
            $entry->expiryDate = DateTimeHelper::toDateTime($expiryDate) ?: null;
        }

        $enabledForSite = $this->enabledForSiteValue();
        if (is_array($enabledForSite)) {
            // Set the global status to true if it's enabled for *any* sites, or if already enabled.
            $entry->enabled = in_array(true, $enabledForSite, false) || $entry->enabled;
        } else {
            $entry->enabled = (bool)$this->request->getBodyParam('enabled', $entry->enabled);
        }
        $entry->setEnabledForSite($enabledForSite ?? $entry->getEnabledForSite());
        $entry->title = $this->request->getBodyParam('title', $entry->title);

        if (!$entry->typeId) {
            // Default to the section's first entry type
            $entry->typeId = $entry->getAvailableEntryTypes()[0]->id;
        }

        // Prevent the last entry type's field layout from being used
        $entry->fieldLayoutId = null;

        $fieldsLocation = $this->request->getParam('fieldsLocation', 'fields');
        $entry->setFieldValuesFromRequest($fieldsLocation);

        // Authors
        $authorIds = $this->request->getBodyParam('authors') ?? $this->request->getBodyParam('author');
        if ($authorIds !== null) {
            $entry->setAuthorIds($authorIds);
        } elseif (!$entry->id) {
            $entry->setAuthor(static::currentUser());
        }

        // Parent
        if (($parentId = $this->request->getBodyParam('parentId')) !== null) {
            $entry->setParentId($parentId);
        }

        // Is fresh?
        if ($this->request->getBodyParam('isFresh')) {
            $entry->setIsFresh();
        }

        // Revision notes
        $entry->setRevisionNotes($this->request->getBodyParam('notes'));
    }
}
