<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use Craft;
use craft\base\Element;
use craft\behaviors\DraftBehavior;
use craft\behaviors\RevisionBehavior;
use craft\db\Table;
use craft\elements\Entry;
use craft\elements\User;
use craft\errors\InvalidElementException;
use craft\errors\UnsupportedSiteException;
use craft\helpers\DateTimeHelper;
use craft\helpers\Db;
use craft\helpers\ElementHelper;
use craft\helpers\UrlHelper;
use craft\models\Section;
use craft\models\Site;
use craft\web\assets\editentry\EditEntryAsset;
use Throwable;
use yii\base\Exception;
use yii\base\InvalidConfigException;
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
     * @event ElementEvent The event that is triggered when an entry’s template is rendered for Live Preview.
     * @deprecated in 3.2.0
     */
    const EVENT_PREVIEW_ENTRY = 'previewEntry';

    /**
     * Called when a user brings up an entry for editing before being displayed.
     *
     * @param string $section The section’s handle
     * @param int|null $entryId The entry’s ID, if editing an existing entry.
     * @param int|null $draftId The entry draft’s ID, if editing an existing draft.
     * @param int|null $revisionId The entry revision’s ID, if editing an existing revision.
     * @param string|null $site The site handle, if specified.
     * @param Entry|null $entry The entry being edited, if there were any validation errors.
     * @return Response
     * @throws NotFoundHttpException if the requested site handle is invalid
     * @throws ForbiddenHttpException
     * @throws BadRequestHttpException
     */
    public function actionEditEntry(string $section, int $entryId = null, int $draftId = null, int $revisionId = null, string $site = null, Entry $entry = null): Response
    {
        if ($draftId && $revisionId) {
            throw new BadRequestHttpException('Only a draftId or revisionId can be specified.');
        }

        $variables = [
            'sectionHandle' => $section,
            'entryId' => $entryId,
            'draftId' => $draftId,
            'revisionId' => $revisionId,
            'entry' => $entry,
        ];

        if ($site !== null) {
            $siteHandle = $site;
            $variables['site'] = Craft::$app->getSites()->getSiteByHandle($siteHandle);

            if (!$variables['site']) {
                throw new NotFoundHttpException('Invalid site handle: ' . $siteHandle);
            }
        }

        if (($response = $this->_prepEditEntryVariables($variables)) !== null) {
            return $response;
        }

        $this->getView()->registerAssetBundle(EditEntryAsset::class);

        /** @var Site $site */
        $site = $variables['site'];
        /** @var Entry $entry */
        $entry = $variables['entry'];
        /** @var Section $section */
        $section = $variables['section'];

        // Make sure they have permission to edit this entry
        $this->enforceSitePermission($entry->getSite());
        $this->enforceEditEntryPermissions($entry);

        $currentUser = Craft::$app->getUser()->getIdentity();
        $variables['permissionSuffix'] = ':' . $entry->getSection()->uid;

        if (Craft::$app->getEdition() === Craft::Pro && $section->type !== Section::TYPE_SINGLE) {
            // Author selector variables
            // ---------------------------------------------------------------------

            $variables['userElementType'] = User::class;

            $authorPermission = 'editEntries' . $variables['permissionSuffix'];

            $variables['authorOptionCriteria'] = [
                'can' => $authorPermission,
            ];

            try {
                if (($variables['author'] = $entry->getAuthor()) === null) {
                    // Default to the current user
                    $variables['author'] = $currentUser;
                }
            } catch (InvalidConfigException $e) {
                // The author doesn't exist anymore
                $variables['author'] = $currentUser;
            }
        }

        // Parent Entry selector variables
        // ---------------------------------------------------------------------

        if (
            $section->type === Section::TYPE_STRUCTURE &&
            (int)$section->maxLevels !== 1
        ) {
            $variables['elementType'] = Entry::class;

            // Prevent the current entry, or any of its descendants, from being options
            $excludeIds = Entry::find()
                ->descendantOf($entry)
                ->drafts(null)
                ->draftOf(false)
                ->anyStatus()
                ->ids();
            $excludeIds[] = $entry->getCanonicalId();

            $variables['parentOptionCriteria'] = [
                'siteId' => $site->id,
                'sectionId' => $section->id,
                'status' => null,
                'where' => ['not in', 'elements.id', $excludeIds],
                'drafts' => null,
                'draftOf' => false,
            ];

            if ($section->maxLevels) {
                if ($entry->id) {
                    // Figure out how deep the ancestors go
                    $maxDepth = Entry::find()
                        ->select('level')
                        ->descendantOf($entry)
                        ->anyStatus()
                        ->leaves()
                        ->scalar();
                    $depth = 1 + ($maxDepth ?: $entry->level) - $entry->level;
                } else {
                    $depth = 1;
                }

                $variables['parentOptionCriteria']['level'] = '<= ' . ($section->maxLevels - $depth);
            }

            // Get the initially selected parent
            $parentId = $this->request->getParam('parentId') ?? $entry->newParentId;

            if ($parentId) {
                $variables['parent'] = Craft::$app->getEntries()->getEntryById(
                    is_array($parentId) ? reset($parentId) : $parentId,
                    $site->id,
                    ['drafts' => null, 'draftOf' => false]
                );
            } else {
                // If the entry already has structure data, use it.
                // Otherwise, use its canonical entry
                $variables['parent'] = Entry::find()
                    ->siteId($entry->siteId)
                    ->ancestorOf($entry->lft ? $entry : ($entry->getIsCanonical() ? $entry->id : $entry->getCanonical(true)))
                    ->ancestorDist(1)
                    ->drafts(null)
                    ->draftOf(false)
                    ->anyStatus()
                    ->one();
            }
        }

        // Other variables
        // ---------------------------------------------------------------------

        // Body class
        $variables['bodyClass'] = 'edit-entry site--' . $site->handle;

        // Breadcrumbs
        $variables['crumbs'] = [
            [
                'label' => Craft::t('app', 'Entries'),
                'url' => UrlHelper::url('entries'),
            ],
        ];

        if ($section->type === Section::TYPE_SINGLE) {
            $variables['crumbs'][] = [
                'label' => Craft::t('app', 'Singles'),
                'url' => UrlHelper::url('entries/singles'),
            ];
        } else {
            $variables['crumbs'][] = [
                'label' => Craft::t('site', $section->name),
                'url' => UrlHelper::url('entries/' . $section->handle),
            ];

            if ($section->type === Section::TYPE_STRUCTURE) {
                /** @var Entry $ancestor */
                foreach ($entry->getCanonical(true)->getAncestors()->all() as $ancestor) {
                    $variables['crumbs'][] = [
                        'label' => $ancestor->title,
                        'url' => $ancestor->getCpEditUrl(),
                    ];
                }
            }
        }

        // Multiple entry types?
        $entryTypes = $entry->getAvailableEntryTypes();

        if (count($entryTypes) > 1) {
            $variables['showEntryTypes'] = true;

            foreach ($entryTypes as $entryType) {
                $variables['entryTypeOptions'][] = [
                    'label' => Craft::t('site', $entryType->name),
                    'value' => $entryType->id,
                ];
            }

            $this->getView()->registerJs('new Craft.EntryTypeSwitcher();');
        } else {
            $variables['showEntryTypes'] = false;
        }

        // Can the user delete the entry?
        $variables['canDeleteSource'] = $entry->getIsDeletable();

        // Can the user delete the entry for the current site?
        $variables['canDeleteForSite'] = $section->propagationMethod === Section::PROPAGATION_METHOD_CUSTOM;

        // Render the template!
        return $this->renderTemplate('entries/_edit', $variables);
    }

    /**
     * Switches between two entry types.
     *
     * @return Response
     * @throws ForbiddenHttpException
     */
    public function actionSwitchEntryType(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $entry = $this->_getEntryModel();
        $this->enforceSitePermission($entry->getSite());
        $this->enforceEditEntryPermissions($entry);
        $this->_populateEntryModel($entry);

        $variables = [];

        $variables['sectionId'] = $entry->sectionId;
        $variables['entry'] = $entry;
        $variables['showEntryTypes'] = true;

        if (($response = $this->_prepEditEntryVariables($variables)) !== null) {
            return $response;
        }

        $view = $this->getView();
        $form = $variables['entryType']->getFieldLayout()->createForm($variables['entry']);
        $tabs = $form->getTabMenu();

        return $this->asJson([
            'tabsHtml' => count($tabs) > 1 ? $view->renderTemplate('_includes/tabs', [
                'tabs' => $tabs,
                'containerAttributes' => [
                    'id' => 'tabs',
                ],
            ]) : null,
            'fieldsHtml' => $form->render(),
            'headHtml' => $view->getHeadHtml(),
            'bodyHtml' => $view->getBodyHtml(),
        ]);
    }

    /**
     * Saves an entry.
     *
     * @param bool $duplicate Whether the entry should be duplicated
     * @return Response|null
     * @throws ServerErrorHttpException if reasons
     * @throws ForbiddenHttpException
     */
    public function actionSaveEntry(bool $duplicate = false)
    {
        $this->requirePostRequest();

        $entry = $this->_getEntryModel();
        $entryVariable = $this->request->getValidatedBodyParam('entryVariable') ?? 'entry';
        // Permission enforcement
        $this->enforceSitePermission($entry->getSite());
        $this->enforceEditEntryPermissions($entry, $duplicate);
        $currentUser = Craft::$app->getUser()->getIdentity();

        // Is this another user's entry (and it's not a Single)?
        if (
            $entry->id &&
            !$duplicate &&
            $entry->authorId != $currentUser->id &&
            $entry->getSection()->type !== Section::TYPE_SINGLE &&
            $entry->enabled
        ) {
            // Make sure they have permission to make live changes to those
            $this->requirePermission('publishPeerEntries:' . $entry->getSection()->uid);
        }

        // Keep track of whether the entry was disabled as a result of duplication
        $forceDisabled = false;

        $transation = Craft::$app->getDb()->beginTransaction();
        try {
            // If we're duplicating the entry, swap $entry with the duplicate
            if ($duplicate) {
                try {
                    $originalEntry = $entry;
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
                        return $this->asJson([
                            'success' => false,
                            'errors' => $clone->getErrors(),
                        ]);
                    }

                    $this->setFailFlash(Craft::t('app', 'Couldn’t duplicate entry.'));

                    // Send the original entry back to the template, with any validation errors on the clone
                    $entry->addErrors($clone->getErrors());
                    Craft::$app->getUrlManager()->setRouteParams([
                        'entry' => $entry,
                    ]);

                    return null;
                } catch (\Throwable $e) {
                    throw new ServerErrorHttpException(Craft::t('app', 'An error occurred when duplicating the entry.'), 0, $e);
                }
            }

            // Populate the entry with post data
            $this->_populateEntryModel($entry);

            if ($forceDisabled) {
                $entry->enabled = false;
            }

            // Even more permission enforcement
            if ($entry->enabled) {
                if ($entry->id) {
                    $this->requirePermission('publishEntries:' . $entry->getSection()->uid);
                } elseif (!$currentUser->can('publishEntries:' . $entry->getSection()->uid)) {
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

            if ($success) {
                $transation->commit();
            } else {
                $transation->rollBack();
            }
        } catch (Throwable $e) {
            $transation->rollBack();
            throw $e;
        }

        if (!$success) {
            if ($this->request->getAcceptsJson()) {
                return $this->asJson([
                    'errors' => $entry->getErrors(),
                ]);
            }

            // If the entry was duplicated, swap it back to the original entry, populated with the errors
            if ($duplicate) {
                $originalEntry->addErrors($entry->getErrors());
                $entry = $originalEntry;
            }


            $this->setFailFlash(Craft::t('app', 'Couldn’t save entry.'));

            // Send the entry back to the template
            Craft::$app->getUrlManager()->setRouteParams([
                $entryVariable => $entry,
            ]);

            return null;
        }

        // See if the user happens to have a provisional entry. If so delete it.
        $provisional = Entry::find()
            ->provisionalDrafts()
            ->draftOf($entry->id)
            ->draftCreator(Craft::$app->getUser()->getIdentity())
            ->siteId($entry->siteId)
            ->anyStatus()
            ->one();

        if ($provisional) {
            Craft::$app->getElements()->deleteElement($provisional, true);
        }

        if ($this->request->getAcceptsJson()) {
            $return = [];

            $return['success'] = true;
            $return['id'] = $entry->id;
            $return['title'] = $entry->title;
            $return['slug'] = $entry->slug;

            if ($this->request->getIsCpRequest()) {
                $return['cpEditUrl'] = $entry->getCpEditUrl();
            }

            if (($author = $entry->getAuthor()) !== null) {
                $return['authorUsername'] = $author->username;
            }

            $return['dateCreated'] = DateTimeHelper::toIso8601($entry->dateCreated);
            $return['dateUpdated'] = DateTimeHelper::toIso8601($entry->dateUpdated);
            $return['postDate'] = ($entry->postDate ? DateTimeHelper::toIso8601($entry->postDate) : null);

            return $this->asJson($return);
        }

        $this->setSuccessFlash(Craft::t('app', 'Entry saved.'));
        return $this->redirectToPostedUrl($entry);
    }

    /**
     * Duplicates an entry.
     *
     * @return Response|null
     * @throws ServerErrorHttpException if reasons
     * @since 3.2.3
     */
    public function actionDuplicateEntry()
    {
        return $this->runAction('save-entry', ['duplicate' => true]);
    }

    /**
     * Deletes an entry for the given site.
     *
     * @return Response|null
     * @throws NotFoundHttpException
     * @throws BadRequestHttpException
     * @since 3.6.0
     */
    public function actionDeleteForSite()
    {
        $this->requirePostRequest();

        // Make sure they have permission to access this site
        $siteId = $this->request->getRequiredBodyParam('siteId');
        $sitesService = Craft::$app->getSites();
        $site = $sitesService->getSiteById($siteId);

        if (!$site) {
            throw new BadRequestHttpException("Invalid site ID: $siteId");
        }

        $this->enforceSitePermission($site);

        // Get the entry in any but the to-be-deleted site -- preferably one the user has access to edit
        $draftId = $this->request->getBodyParam('draftId');
        $entryId = $this->request->getBodyParam('sourceId');
        $provisional = (bool)($this->request->getBodyParam('provisional') ?? false);
        $editableSiteIds = $sitesService->getEditableSiteIds();

        $query = Entry::find()
            ->siteId(['not', $siteId])
            ->preferSites($editableSiteIds)
            ->unique()
            ->anyStatus();

        if ($draftId) {
            $query
                ->draftId($draftId)
                ->provisionalDrafts($provisional);
        } else {
            $query->id($entryId);
        }

        $entry = $query->one();
        if (!$entry) {
            throw new NotFoundHttpException('Entry not found');
        }

        $this->enforceEditEntryPermissions($entry);
        $this->enforceDeleteEntryPermissions($entry);

        // Delete the row in elements_sites
        Db::delete(Table::ELEMENTS_SITES, [
            'elementId' => $entry->id,
            'siteId' => $siteId,
        ]);

        // Resave the entry
        $entry->setScenario(Element::SCENARIO_ESSENTIALS);
        $entry->resaving = true;
        Craft::$app->getElements()->saveElement($entry, true, true, false);

        if ($draftId && !$provisional) {
            $this->setSuccessFlash(Craft::t('app', 'Draft deleted for site.'));
        } else {
            $this->setSuccessFlash(Craft::t('app', 'Entry deleted for site.'));
        }

        if (!in_array($entry->siteId, $editableSiteIds)) {
            // That was the only site they had access to, so send them back to the Entries index
            return $this->redirect('entries');
        }

        if ($draftId) {
            // Redirect to the same draft in the fetched site
            return $this->redirect(UrlHelper::url($entry->getCanonical()->getCpEditUrl(), [
                'siteId' => $entry->siteId,
                'draftId' => $draftId,
            ]));
        }

        // Redirect them to the same entry in the fetched site
        return $this->redirect($entry->getCpEditUrl());
    }

    /**
     * Deletes an entry.
     *
     * @return Response|null
     * @throws BadRequestHttpException if the requested entry cannot be found
     */
    public function actionDeleteEntry()
    {
        $this->requirePostRequest();

        $entryId = $this->request->getBodyParam('sourceId') ?? $this->request->getRequiredBodyParam('entryId');
        $siteId = $this->request->getBodyParam('siteId');
        $entry = Craft::$app->getEntries()->getEntryById($entryId, $siteId);

        if (!$entry) {
            throw new BadRequestHttpException("Invalid entry ID: $entryId");
        }

        $this->enforceDeleteEntryPermissions($entry);

        if (!Craft::$app->getElements()->deleteElement($entry)) {
            if ($this->request->getAcceptsJson()) {
                return $this->asJson(['success' => false]);
            }

            $this->setFailFlash(Craft::t('app', 'Couldn’t delete entry.'));

            // Send the entry back to the template
            Craft::$app->getUrlManager()->setRouteParams([
                'entry' => $entry,
            ]);

            return null;
        }

        if ($this->request->getAcceptsJson()) {
            return $this->asJson(['success' => true]);
        }

        $this->setSuccessFlash(Craft::t('app', 'Entry deleted.'));
        return $this->redirectToPostedUrl($entry);
    }

    /**
     * Preps entry edit variables.
     *
     * @param array &$variables
     * @return Response|null
     * @throws NotFoundHttpException if the requested section or entry cannot be found
     * @throws ForbiddenHttpException if the user is not permitted to edit content in the requested site
     */
    private function _prepEditEntryVariables(array &$variables)
    {
        // Get the section
        // ---------------------------------------------------------------------

        if (!empty($variables['sectionHandle'])) {
            $variables['section'] = Craft::$app->getSections()->getSectionByHandle($variables['sectionHandle']);
        } elseif (!empty($variables['sectionId'])) {
            $variables['section'] = Craft::$app->getSections()->getSectionById($variables['sectionId']);
        }

        if (empty($variables['section'])) {
            throw new NotFoundHttpException('Section not found');
        }

        // Get the site
        // ---------------------------------------------------------------------

        $siteIds = $this->editableSiteIds($variables['section']);

        if (empty($variables['site'])) {
            /** @noinspection PhpUnhandledExceptionInspection */
            $variables['site'] = Craft::$app->getSites()->getCurrentSite();

            if (!in_array($variables['site']->id, $siteIds, false)) {
                $variables['site'] = Craft::$app->getSites()->getSiteById($siteIds[0]);
            }

            $site = $variables['site'];
        } else {
            // Make sure they were requesting a valid site
            /** @var Site $site */
            $site = $variables['site'];
            if (!in_array($site->id, $siteIds, false)) {
                throw new ForbiddenHttpException('User not permitted to edit content in this site');
            }
        }

        // Get the entry
        // ---------------------------------------------------------------------

        if (empty($variables['entry'])) {
            if (empty($variables['entryId'])) {
                throw new BadRequestHttpException('Request missing required entryId param');
            }

            $variables['entry'] = $this->_loadEntry(
                $site,
                $variables['section'],
                $variables['entryId'],
                $variables['draftId'] ?? null,
                $variables['revisionId'] ?? null
            );

            if (!$variables['entry']) {
                // If they're attempting to access a draft/revision, or if the entry may be available in another
                // site, try to redirect them
                if (
                    count($siteIds) > 1 ||
                    !empty($variables['draftId']) ||
                    !empty($variables['revisionId'])
                ) {
                    $sourceEntry = Entry::find()
                        ->id($variables['entryId'])
                        ->siteId($siteIds)
                        ->preferSites([$site->id])
                        ->unique()
                        ->anyStatus()
                        ->one();
                    if ($sourceEntry) {
                        return $this->redirect($sourceEntry->getCpEditUrl(), 301);
                    }
                }
                throw new NotFoundHttpException('Entry not found');
            }
        }

        /** @var Entry|DraftBehavior|RevisionBehavior $entry */
        $entry = $variables['entry'];

        // If this is an outdated draft, merge in the latest canonical changes
        if ($entry->getIsDraft() && $entry->getIsDerivative() && ElementHelper::isOutdated($entry)) {
            Craft::$app->getElements()->mergeCanonicalChanges($entry);
            $variables['notices'][] = Craft::t('app', 'Recent changes to the Current revision have been merged into this draft.');
        }

        // Determine whether we're showing the site label & site-specific entry status
        // ---------------------------------------------------------------------

        // Show the site label in the revision menu as long as the section is enabled for multiple sites,
        // even if the entry itself is only enabled for one site
        $variables['showSiteLabel'] = (
            Craft::$app->getIsMultiSite() &&
            count($variables['section']->getSiteSettings()) > 1
        );

        $variables['isMultiSiteEntry'] = (
            Craft::$app->getIsMultiSite() &&
            count($entry->getSupportedSites()) > 1
        );

        // Get the entry type
        // ---------------------------------------------------------------------

        // Override the entry type?
        $typeId = $this->request->getParam('typeId');

        if (!$typeId) {
            // Default to the section's first entry type
            $typeId = $entry->typeId ?? $entry->getAvailableEntryTypes()[0]->id;
        }

        $entry->typeId = $typeId;
        $variables['entryType'] = $entry->getType();

        // Prevent the last entry type's field layout from being used
        $entry->fieldLayoutId = null;

        return null;
    }

    /**
     * Loads the requested entry.
     *
     * @param Site $site
     * @param Section $section
     * @param int $entryId
     * @param int|null $draftId
     * @param int|null $revisionId
     * @return Entry|null
     */
    private function _loadEntry(Site $site, Section $section, int $entryId, int $draftId = null, int $revisionId = null)
    {
        if ($draftId || $revisionId) {
            $entry = Entry::find()
                ->draftId($draftId)
                ->revisionId($revisionId)
                ->structureId($section->structureId)
                ->siteId($site->id)
                ->anyStatus()
                ->one();
        } else {
            // First check if there's a provisional draft
            $entry = Entry::find()
                ->provisionalDrafts()
                ->draftOf($entryId)
                ->draftCreator(Craft::$app->getUser()->getIdentity())
                ->structureId($section->structureId)
                ->siteId($site->id)
                ->anyStatus()
                ->one();

            if (!$entry) {
                // Otherwise load the real Current revision
                $entry = Entry::find()
                    ->id($entryId)
                    ->structureId($section->structureId)
                    ->siteId($site->id)
                    ->anyStatus()
                    ->one();
            }
        }

        return $entry;
    }

    /**
     * Fetches or creates an Entry.
     *
     * @return Entry
     * @throws NotFoundHttpException if the requested entry cannot be found
     */
    private function _getEntryModel(): Entry
    {
        $entryId = $this->request->getBodyParam('sourceId') ?? $this->request->getBodyParam('entryId');
        $siteId = $this->request->getBodyParam('siteId');

        if ($entryId) {
            // Is this a provisional draft?
            $provisional = $this->request->getBodyParam('provisional');
            if ($provisional) {
                $entry = Entry::find()
                    ->provisionalDrafts()
                    ->draftOf($entryId)
                    ->draftCreator(Craft::$app->getUser()->getIdentity())
                    ->siteId($siteId)
                    ->anyStatus()
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
    private function _populateEntryModel(Entry $entry)
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
        $authorId = $this->request->getBodyParam('author', ($entry->authorId ?: Craft::$app->getUser()->getIdentity()->id));

        if (is_array($authorId)) {
            $authorId = $authorId[0] ?? null;
        }

        $entry->authorId = $authorId;

        // Parent
        if (($parentId = $this->request->getBodyParam('parentId')) !== null) {
            if (is_array($parentId)) {
                $parentId = reset($parentId) ?: false;
            }
            $entry->newParentId = $parentId ?: false;
        }

        // Is fresh?
        if ($this->request->getBodyParam('isFresh')) {
            $entry->setIsFresh();
        }

        // Revision notes
        $entry->setRevisionNotes($this->request->getBodyParam('notes'));
    }
}
