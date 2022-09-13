<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use Craft;
use craft\base\Element;
use craft\elements\Entry;
use craft\errors\InvalidElementException;
use craft\errors\UnsupportedSiteException;
use craft\helpers\ArrayHelper;
use craft\helpers\Cp;
use craft\helpers\DateTimeHelper;
use craft\helpers\ElementHelper;
use craft\helpers\UrlHelper;
use craft\models\Section;
use craft\models\Section_SiteSettings;
use Throwable;
use yii\base\Exception;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\web\ServerErrorHttpException;

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
     * Creates a new unpublished draft and redirects to its edit page.
     *
     * @param string|null $section The section’s handle
     * @return Response|null
     * @throws BadRequestHttpException
     * @throws ForbiddenHttpException
     * @throws ServerErrorHttpException
     */
    public function actionCreate(?string $section = null): ?Response
    {
        if ($section) {
            $sectionHandle = $section;
        } else {
            $sectionHandle = $this->request->getRequiredBodyParam('section');
        }

        $section = Craft::$app->getSections()->getSectionByHandle($sectionHandle);
        if (!$section) {
            throw new BadRequestHttpException("Invalid section handle: $sectionHandle");
        }

        $sitesService = Craft::$app->getSites();
        $siteId = $this->request->getBodyParam('siteId');

        if ($siteId) {
            $site = $sitesService->getSiteById($siteId);
            if (!$site) {
                throw new BadRequestHttpException("Invalid site ID: $siteId");
            }
        } else {
            $site = Cp::requestedSite();
            if (!$site) {
                throw new ForbiddenHttpException('User not authorized to edit content in any sites.');
            }
        }

        $editableSiteIds = $this->editableSiteIds($section);

        if (!in_array($site->id, $editableSiteIds)) {
            // If there’s more than one possibility and entries doesn’t propagate to all sites, let the user choose
            if (count($editableSiteIds) > 1 && $section->propagationMethod !== Section::PROPAGATION_METHOD_ALL) {
                return $this->renderTemplate('_special/sitepicker.twig', [
                    'siteIds' => $editableSiteIds,
                    'baseUrl' => "entries/$section->handle/new",
                ]);
            }

            // Go with the first one
            $site = $sitesService->getSiteById($editableSiteIds[0]);
        }

        $user = static::currentUser();

        // Create & populate the draft
        $entry = Craft::createObject(Entry::class);
        $entry->siteId = $site->id;
        $entry->sectionId = $section->id;
        $entry->authorId = $this->request->getQueryParam('authorId', $user->id);

        // Type
        if (($typeHandle = $this->request->getQueryParam('type')) !== null) {
            $type = ArrayHelper::firstWhere($entry->getAvailableEntryTypes(), 'handle', $typeHandle);
            if ($type === null) {
                throw new BadRequestHttpException("Invalid entry type handle: $typeHandle");
            }
            $entry->typeId = $type->id;
        } else {
            $entry->typeId = $this->request->getQueryParam('typeId') ?? $entry->getAvailableEntryTypes()[0]->id;
        }

        // Status
        if (($status = $this->request->getQueryParam('status')) !== null) {
            $enabled = $status === 'enabled';
        } else {
            // Set the default status based on the section's settings
            /** @var Section_SiteSettings $siteSettings */
            $siteSettings = ArrayHelper::firstWhere($section->getSiteSettings(), 'siteId', $entry->siteId);
            $enabled = $siteSettings->enabledByDefault;
        }
        if (Craft::$app->getIsMultiSite() && count($entry->getSupportedSites()) > 1) {
            $entry->enabled = true;
            $entry->setEnabledForSite($enabled);
        } else {
            $entry->enabled = $enabled;
            $entry->setEnabledForSite(true);
        }

        // Structure parent
        if (
            $section->type === Section::TYPE_STRUCTURE &&
            (int)$section->maxLevels !== 1
        ) {
            // Set the initially selected parent
            $entry->setParentId($this->request->getParam('parentId'));
        }

        // Make sure the user is allowed to create this entry
        if (!Craft::$app->getElements()->canSave($entry, $user)) {
            throw new ForbiddenHttpException('User not authorized to save this entry.');
        }

        // Title & slug
        $entry->title = $this->request->getQueryParam('title');
        $entry->slug = $this->request->getQueryParam('slug');
        if ($entry->title && !$entry->slug) {
            $entry->slug = ElementHelper::generateSlug($entry->title, null, $site->language);
        }
        if (!$entry->slug) {
            $entry->slug = ElementHelper::tempSlug();
        }

        // Pause time so postDate will definitely be equal to dateCreated, if not explicitly defined
        DateTimeHelper::pause();

        // Post & expiry dates
        if (($postDate = $this->request->getQueryParam('postDate')) !== null) {
            $entry->postDate = DateTimeHelper::toDateTime($postDate);
        } else {
            $entry->postDate = DateTimeHelper::now();
        }

        if (($expiryDate = $this->request->getQueryParam('expiryDate')) !== null) {
            $entry->expiryDate = DateTimeHelper::toDateTime($expiryDate);
        }

        // Custom fields
        foreach ($entry->getFieldLayout()->getCustomFields() as $field) {
            if (($value = $this->request->getQueryParam($field->handle)) !== null) {
                $entry->setFieldValue($field->handle, $value);
            }
        }

        // Save it
        $entry->setScenario(Element::SCENARIO_ESSENTIALS);
        $success = Craft::$app->getDrafts()->saveElementAsDraft($entry, Craft::$app->getUser()->getId(), null, null, false);

        // Resume time
        DateTimeHelper::resume();

        if (!$success) {
            return $this->asModelFailure($entry, Craft::t('app', 'Couldn’t create {type}.', [
                'type' => Entry::lowerDisplayName(),
            ]), 'entry');
        }

        // Set its position in the structure if a before/after param was passed
        if ($section->type === Section::TYPE_STRUCTURE) {
            if ($nextId = $this->request->getParam('before')) {
                $nextEntry = Craft::$app->getEntries()->getEntryById($nextId, $site->id, [
                    'structureId' => $section->structureId,
                ]);
                Craft::$app->getStructures()->moveBefore($section->structureId, $entry, $nextEntry);
            } elseif ($prevId = $this->request->getParam('after')) {
                $prevEntry = Craft::$app->getEntries()->getEntryById($prevId, $site->id, [
                    'structureId' => $section->structureId,
                ]);
                Craft::$app->getStructures()->moveAfter($section->structureId, $entry, $prevEntry);
            }
        }

        $editUrl = $entry->getCpEditUrl();

        $response = $this->asModelSuccess($entry, Craft::t('app', '{type} created.', [
            'type' => Entry::displayName(),
        ]), 'entry', array_filter([
            'cpEditUrl' => $this->request->isCpRequest ? $editUrl : null,
        ]));

        if (!$this->request->getAcceptsJson()) {
            $response->redirect(UrlHelper::urlWithParams($editUrl, [
                'fresh' => 1,
            ]));
        }

        return $response;
    }

    /**
     * Saves an entry.
     *
     * @param bool $duplicate Whether the entry should be duplicated
     * @return Response|null
     * @throws ServerErrorHttpException if reasons
     * @throws ForbiddenHttpException
     */
    public function actionSaveEntry(bool $duplicate = false): ?Response
    {
        $this->requirePostRequest();

        $entry = $this->_getEntryModel();
        $entryVariable = $this->request->getValidatedBodyParam('entryVariable') ?? 'entry';
        // Permission enforcement
        $this->enforceSitePermission($entry->getSite());
        $this->enforceEditEntryPermissions($entry, $duplicate);
        $currentUser = static::currentUser();
        $section = $entry->getSection();

        // Is this another user’s entry (and it’s not a Single)?
        if (
            $entry->id &&
            !$duplicate &&
            $entry->authorId != $currentUser->id &&
            $section->type !== Section::TYPE_SINGLE &&
            $entry->enabled
        ) {
            // Make sure they have permission to make live changes to those
            $this->requirePermission("savePeerEntries:$section->uid");
        }

        // Keep track of whether the entry was disabled as a result of duplication
        $forceDisabled = false;

        // If we're duplicating the entry, swap $entry with the duplicate
        if ($duplicate) {
            try {
                $wasEnabled = $entry->enabled;
                $entry->draftId = null;
                $entry->isProvisionalDraft = false;
                $entry = Craft::$app->getElements()->duplicateElement($entry);
                if ($wasEnabled && !$entry->enabled) {
                    $forceDisabled = true;
                }
            } catch (InvalidElementException $e) {
                /** @var Entry $clone */
                $clone = $e->element;

                if ($this->request->getAcceptsJson()) {
                    return $this->asModelFailure($clone);
                }

                // Send the original entry back to the template, with any validation errors on the clone
                $entry->addErrors($clone->getErrors());

                return $this->asModelFailure(
                    $entry,
                    Craft::t('app', 'Couldn’t duplicate entry.'),
                    'entry'
                );
            } catch (Throwable $e) {
                throw new ServerErrorHttpException(Craft::t('app', 'An error occurred when duplicating the entry.'), 0, $e);
            }
        }

        // Populate the entry with post data
        $this->_populateEntryModel($entry);

        if ($forceDisabled) {
            $entry->enabled = false;
        }

        $section = $entry->getSection();

        // Even more permission enforcement
        if ($entry->enabled) {
            if ($entry->id) {
                $this->requirePermission("saveEntries:$section->uid");
            } elseif (!$currentUser->can("saveEntries:$section->uid")) {
                $entry->enabled = false;
            }
        }

        // Save the entry (finally!)
        if ($entry->enabled && $entry->getEnabledForSite()) {
            $entry->setScenario(Element::SCENARIO_LIVE);
        }

        $isNotNew = (bool)$entry->id;
        if ($isNotNew) {
            $lockKey = "entry:$entry->id";
            $mutex = Craft::$app->getMutex();
            if (!$mutex->acquire($lockKey, 15)) {
                throw new Exception('Could not acquire a lock to save the entry.');
            }
        }

        try {
            $success = Craft::$app->getElements()->saveElement($entry);
        } catch (UnsupportedSiteException $e) {
            $entry->addError('siteId', $e->getMessage());
            $success = false;
        } finally {
            if ($isNotNew) {
                $mutex->release($lockKey);
            }
        }

        if (!$success) {
            return $this->asModelFailure(
                $entry,
                Craft::t('app', 'Couldn’t save entry.'),
                $entryVariable
            );
        }

        // See if the user happens to have a provisional entry. If so delete it.
        /** @var Entry|null $provisional */
        $provisional = Entry::find()
            ->provisionalDrafts()
            ->draftOf($entry->id)
            ->draftCreator(static::currentUser())
            ->siteId($entry->siteId)
            ->status(null)
            ->one();

        if ($provisional) {
            Craft::$app->getElements()->deleteElement($provisional, true);
        }

        $data = [];

        if ($this->request->getAcceptsJson()) {
            $data['id'] = $entry->id;
            $data['title'] = $entry->title;
            $data['slug'] = $entry->slug;

            if ($this->request->getIsCpRequest()) {
                $data['cpEditUrl'] = $entry->getCpEditUrl();
            }

            if (($author = $entry->getAuthor()) !== null) {
                $data['authorUsername'] = $author->username;
            }

            $data['dateCreated'] = DateTimeHelper::toIso8601($entry->dateCreated);
            $data['dateUpdated'] = DateTimeHelper::toIso8601($entry->dateUpdated);
            $data['postDate'] = ($entry->postDate ? DateTimeHelper::toIso8601($entry->postDate) : null);

            if ($this->request->getIsCpRequest()) {
                $data['elementHtml'] = Cp::elementHtml($entry);
            }
        }

        return $this->asModelSuccess(
            $entry,
            Craft::t('app', '{type} saved.', ['type' => Entry::displayName()]),
            data: $data,
        );
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

            $entry = Craft::$app->getEntries()->getEntryById($entryId, $siteId);

            if ($entry) {
                return $entry;
            }

            throw new NotFoundHttpException('Entry not found');
        }

        $entry = new Entry();
        $entry->sectionId = $this->request->getRequiredBodyParam('sectionId');

        if ($siteId) {
            $entry->siteId = $siteId;
        }

        return $entry;
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

        // Author
        $authorId = $this->request->getBodyParam('author', ($entry->authorId ?: static::currentUser()->id));

        if (is_array($authorId)) {
            $authorId = $authorId[0] ?? null;
        }

        $entry->authorId = $authorId;

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
