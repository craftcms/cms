<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use Craft;
use craft\base\Element;
use craft\base\Field;
use craft\behaviors\DraftBehavior;
use craft\behaviors\RevisionBehavior;
use craft\db\Query;
use craft\elements\Entry;
use craft\elements\User;
use craft\errors\InvalidElementException;
use craft\events\ElementEvent;
use craft\helpers\ArrayHelper;
use craft\helpers\DateTimeHelper;
use craft\helpers\Json;
use craft\helpers\StringHelper;
use craft\helpers\UrlHelper;
use craft\models\Section;
use craft\models\Section_SiteSettings;
use craft\models\Site;
use craft\web\assets\editentry\EditEntryAsset;
use DateTime;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\web\ServerErrorHttpException;

/** @noinspection ClassOverridesFieldOfSuperClassInspection */

/**
 * The EntriesController class is a controller that handles various entry related tasks such as retrieving, saving,
 * swapping between entry types, previewing, deleting and sharing entries.
 * Note that all actions in the controller except [[actionViewSharedEntry]] require an authenticated Craft session
 * via [[allowAnonymous]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class EntriesController extends BaseEntriesController
{
    // Constants
    // =========================================================================

    /**
     * @event ElementEvent The event that is triggered when an entry’s template is rendered for Live Preview.
     */
    const EVENT_PREVIEW_ENTRY = 'previewEntry';

    // Properties
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected $allowAnonymous = ['view-shared-entry'];

    // Public Methods
    // =========================================================================

    /**
     * Called when a user beings up an entry for editing before being displayed.
     *
     * @param string $section The section’s handle
     * @param int|null $entryId The entry’s ID, if editing an existing entry.
     * @param int|null $draftId The entry draft’s ID, if editing an existing draft.
     * @param int|null $revisionId The entry revision’s ID, if editing an existing revision.
     * @param string|null $site The site handle, if specified.
     * @param Entry|null $entry The entry being edited, if there were any validation errors.
     * @return Response
     * @throws NotFoundHttpException if the requested site handle is invalid
     */
    public function actionEditEntry(string $section, int $entryId = null, int $draftId = null, int $revisionId = null, string $site = null, Entry $entry = null): Response
    {
        $variables = [
            'sectionHandle' => $section,
            'entryId' => $entryId,
            'draftId' => $draftId,
            'revisionId' => $revisionId,
            'entry' => $entry
        ];

        if ($site !== null) {
            $siteHandle = $site;
            $variables['site'] = Craft::$app->getSites()->getSiteByHandle($siteHandle);

            if (!$variables['site']) {
                throw new NotFoundHttpException('Invalid site handle: ' . $siteHandle);
            }
        }

        $this->_prepEditEntryVariables($variables);

        $this->getView()->registerAssetBundle(EditEntryAsset::class);

        /** @var Site $site */
        $site = $variables['site'];
        /** @var Entry $entry */
        $entry = $variables['entry'];
        /** @var Section $section */
        $section = $variables['section'];

        // Make sure they have permission to edit this entry
        $this->enforceEditEntryPermissions($entry);

        $currentUser = Craft::$app->getUser()->getIdentity();
        $request = Craft::$app->getRequest();

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
                ->anyStatus()
                ->ids();
            $excludeIds[] = $entry->id;

            $variables['parentOptionCriteria'] = [
                'siteId' => $site->id,
                'sectionId' => $section->id,
                'status' => null,
                'enabledForSite' => false,
                'where' => ['not in', 'elements.id', $excludeIds]
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
            $parentId = $request->getParam('parentId');

            if ($parentId === null) {
                // Is it already set on the model (e.g. if we're loading a draft)?
                if ($entry->newParentId !== null) {
                    $parentId = $entry->newParentId;
                } else {
                    $parentId = $entry->getAncestors(1)
                        ->anyStatus()
                        ->ids();
                }
            }

            if (is_array($parentId)) {
                $parentId = reset($parentId) ?: null;
            }

            if ($parentId) {
                $variables['parent'] = Craft::$app->getEntries()->getEntryById($parentId, $site->id);
            }
        }

        // Enabled sites
        // ---------------------------------------------------------------------

        if (Craft::$app->getIsMultiSite()) {
            $variables['enabledSiteIds'] = Craft::$app->getElements()->getEnabledSiteIdsForElement($entry->id);
        }

        // Other variables
        // ---------------------------------------------------------------------

        // Body class
        $variables['bodyClass'] = 'edit-entry site--' . $site->handle;

        // Page title
        $variables['docTitle'] = $this->docTitle($entry);
        $variables['title'] = $this->pageTitle($entry);

        // Breadcrumbs
        $variables['crumbs'] = [
            [
                'label' => Craft::t('app', 'Entries'),
                'url' => UrlHelper::url('entries')
            ]
        ];

        if ($section->type === Section::TYPE_SINGLE) {
            $variables['crumbs'][] = [
                'label' => Craft::t('app', 'Singles'),
                'url' => UrlHelper::url('entries/singles')
            ];
        } else {
            $variables['crumbs'][] = [
                'label' => Craft::t('site', $section->name),
                'url' => UrlHelper::url('entries/' . $section->handle)
            ];

            if ($section->type === Section::TYPE_STRUCTURE) {
                /** @var Entry $ancestor */
                foreach ($entry->getAncestors()->all() as $ancestor) {
                    $variables['crumbs'][] = [
                        'label' => $ancestor->title,
                        'url' => $ancestor->getCpEditUrl()
                    ];
                }
            }
        }

        // Multiple entry types?
        $entryTypes = $section->getEntryTypes();

        if (count($entryTypes) > 1) {
            $variables['showEntryTypes'] = true;

            foreach ($entryTypes as $entryType) {
                $variables['entryTypeOptions'][] = [
                    'label' => Craft::t('site', $entryType->name),
                    'value' => $entryType->id
                ];
            }

            $this->getView()->registerJs('new Craft.EntryTypeSwitcher();');
        } else {
            $variables['showEntryTypes'] = false;
        }

        // Enable Live Preview?
        if (!$request->isMobileBrowser(true) && Craft::$app->getSections()->isSectionTemplateValid($section, $entry->siteId)) {
            $this->getView()->registerJs('Craft.LivePreview.init(' . Json::encode([
                    'fields' => '#title-field, #fields > div > div > .field',
                    'extraFields' => '#settings',
                    'previewUrl' => $entry->getUrl(),
                    'previewAction' => Craft::$app->getSecurity()->hashData('entries/preview-entry'),
                    'previewParams' => [
                        'sectionId' => $section->id,
                        'entryId' => $entry->getSourceId(),
                        'siteId' => $entry->siteId,
                        'revisionId' => $entry->revisionId,
                    ]
                ]) . ');');

            $variables['showPreviewBtn'] = true;

            if ($entry->getIsDraft()) {
                /** @var Entry|DraftBehavior $entry */
                $variables['shareUrl'] = UrlHelper::actionUrl('entries/share-entry', [
                    'draftId' => $entry->draftId,
                ]);
            } else if ($entry->getIsRevision()) {
                /** @var Entry|RevisionBehavior $entry */
                $variables['shareUrl'] = UrlHelper::actionUrl('entries/share-entry', [
                    'revisionId' => $entry->revisionId,
                ]);
            } else if ($entry->getStatus() === Entry::STATUS_LIVE) {
                // just use the entry's main URL as its share URL
                $variables['shareUrl'] = $entry->getUrl();
            } else {
                $variables['shareUrl'] = UrlHelper::actionUrl('entries/share-entry', [
                    'entryId' => $entry->id,
                    'siteId' => $entry->siteId,
                ]);
            }
        } else {
            $variables['showPreviewBtn'] = false;
        }

        // Can the user delete the entry?
        $variables['canDeleteEntry'] = (
            ($entry->authorId == $currentUser->id && $currentUser->can('deleteEntries' . $variables['permissionSuffix'])) ||
            ($entry->authorId != $currentUser->id && $currentUser->can('deletePeerEntries' . $variables['permissionSuffix']))
        );

        // Render the template!
        return $this->renderTemplate('entries/_edit', $variables);
    }

    /**
     * Switches between two entry types.
     *
     * @return Response
     */
    public function actionSwitchEntryType(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $entry = $this->_getEntryModel();
        $this->enforceEditEntryPermissions($entry);
        $this->_populateEntryModel($entry);

        $variables = [];

        $variables['sectionId'] = $entry->sectionId;
        $variables['entry'] = $entry;
        $variables['showEntryTypes'] = true;

        $this->_prepEditEntryVariables($variables);

        $view = $this->getView();
        $tabsHtml = !empty($variables['tabs']) ? $view->renderTemplate('_includes/tabs', $variables) : null;
        $fieldsHtml = $view->renderTemplate('entries/_fields', $variables);
        $headHtml = $view->getHeadHtml();
        $bodyHtml = $view->getBodyHtml();

        return $this->asJson(compact(
            'tabsHtml',
            'fieldsHtml',
            'headHtml',
            'bodyHtml'
        ));
    }

    /**
     * Previews an entry.
     *
     * @return Response
     * @throws NotFoundHttpException if the requested entry version cannot be found
     */
    public function actionPreviewEntry(): Response
    {
        $this->requirePostRequest();

        // Are we previewing a version?
        $revisionId = Craft::$app->getRequest()->getBodyParam('revisionId');

        if ($revisionId) {
            $entry = Entry::find()
                ->revisionId($revisionId)
                ->siteId(Craft::$app->getRequest()->getBodyParam('siteId'))
                ->anyStatus()
                ->one();

            if (!$entry) {
                throw new NotFoundHttpException('Entry version not found');
            }

            $this->enforceEditEntryPermissions($entry);
        } else {
            $entry = $this->_getEntryModel();
            $this->enforceEditEntryPermissions($entry);

            // Set the language to the user's preferred language so DateFormatter returns the right format
            Craft::$app->updateTargetLanguage(true);

            $this->_populateEntryModel($entry);
        }

        // Fire a 'previewEntry' event
        if ($this->hasEventHandlers(self::EVENT_PREVIEW_ENTRY)) {
            $this->trigger(self::EVENT_PREVIEW_ENTRY, new ElementEvent([
                'element' => $entry,
            ]));
        }

        return $this->_showEntry($entry);
    }

    /**
     * Saves an entry.
     *
     * @return Response|null
     * @throws ServerErrorHttpException if reasons
     */
    public function actionSaveEntry()
    {
        $this->requirePostRequest();

        $entry = $this->_getEntryModel();
        $request = Craft::$app->getRequest();

        // Are we duplicating the entry?
        $duplicate = (bool)$request->getBodyParam('duplicate');

        // Permission enforcement
        $this->enforceEditEntryPermissions($entry, $duplicate);
        $currentUser = Craft::$app->getUser()->getIdentity();

        // Is this another user's entry (and it's not a Single)?
        if (
            $entry->id &&
            $entry->authorId != $currentUser->id &&
            $entry->getSection()->type !== Section::TYPE_SINGLE &&
            $entry->enabled
        ) {
            // Make sure they have permission to make live changes to those
            $this->requirePermission('publishPeerEntries:' . $entry->getSection()->uid);
        }

        // If we're duplicating the entry, swap $entry with the duplicate
        if ($duplicate) {
            try {
                $entry = Craft::$app->getElements()->duplicateElement($entry);
            } catch (InvalidElementException $e) {
                /** @var Entry $clone */
                $clone = $e->element;

                if ($request->getAcceptsJson()) {
                    return $this->asJson([
                        'success' => false,
                        'errors' => $clone->getErrors(),
                    ]);
                }

                Craft::$app->getSession()->setError(Craft::t('app', 'Couldn’t duplicate entry.'));

                // Send the original entry back to the template, with any validation errors on the clone
                $entry->addErrors($clone->getErrors());
                Craft::$app->getUrlManager()->setRouteParams([
                    'entry' => $entry
                ]);

                return null;
            } catch (\Throwable $e) {
                throw new ServerErrorHttpException(Craft::t('app', 'An error occurred when duplicating the entry.'), 0, $e);
            }
        }

        // Populate the entry with post data
        $this->_populateEntryModel($entry);

        // Even more permission enforcement
        if ($entry->enabled) {
            if ($entry->id) {
                $this->requirePermission('publishEntries:' . $entry->getSection()->uid);
            } else if (!$currentUser->can('publishEntries:' . $entry->getSection()->uid)) {
                $entry->enabled = false;
            }
        }

        // Save the entry (finally!)
        if ($entry->enabled && $entry->enabledForSite) {
            $entry->setScenario(Element::SCENARIO_LIVE);
        }

        if (!Craft::$app->getElements()->saveElement($entry)) {
            if ($request->getAcceptsJson()) {
                return $this->asJson([
                    'errors' => $entry->getErrors(),
                ]);
            }

            Craft::$app->getSession()->setError(Craft::t('app', 'Couldn’t save entry.'));

            // Send the entry back to the template
            Craft::$app->getUrlManager()->setRouteParams([
                'entry' => $entry
            ]);

            return null;
        }

        if ($request->getAcceptsJson()) {
            $return = [];

            $return['success'] = true;
            $return['id'] = $entry->id;
            $return['title'] = $entry->title;
            $return['slug'] = $entry->slug;

            if ($request->getIsCpRequest()) {
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

        Craft::$app->getSession()->setNotice(Craft::t('app', 'Entry saved.'));

        return $this->redirectToPostedUrl($entry);
    }

    /**
     * Deletes an entry.
     *
     * @return Response|null
     * @throws NotFoundHttpException if the requested entry cannot be found
     */
    public function actionDeleteEntry()
    {
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();
        $entryId = $request->getRequiredBodyParam('entryId');
        $siteId = $request->getBodyParam('siteId');
        $entry = Craft::$app->getEntries()->getEntryById($entryId, $siteId);

        if (!$entry) {
            throw new NotFoundHttpException('Entry not found');
        }

        $currentUser = Craft::$app->getUser()->getIdentity();

        if ($entry->authorId == $currentUser->id) {
            $this->requirePermission('deleteEntries:' . $entry->getSection()->uid);
        } else {
            $this->requirePermission('deletePeerEntries:' . $entry->getSection()->uid);
        }

        if (!Craft::$app->getElements()->deleteElement($entry)) {
            if ($request->getAcceptsJson()) {
                return $this->asJson(['success' => false]);
            }

            Craft::$app->getSession()->setError(Craft::t('app', 'Couldn’t delete entry.'));

            // Send the entry back to the template
            Craft::$app->getUrlManager()->setRouteParams([
                'entry' => $entry
            ]);

            return null;
        }

        if ($request->getAcceptsJson()) {
            return $this->asJson(['success' => true]);
        }

        Craft::$app->getSession()->setNotice(Craft::t('app', 'Entry deleted.'));

        return $this->redirectToPostedUrl($entry);
    }

    /**
     * Redirects the client to a URL for viewing an entry/draft/version on the front end.
     *
     * @param int|null $entryId
     * @param int|null $siteId
     * @param int|null $draftId
     * @param int|null $revisionId
     * @return Response
     * @throws Exception
     * @throws NotFoundHttpException if the requested entry/revision cannot be found
     * @throws ServerErrorHttpException if the section is not configured properly
     */
    public function actionShareEntry(int $entryId = null, int $siteId = null, int $draftId = null, int $revisionId = null): Response
    {
        if ($entryId !== null) {
            $entry = Craft::$app->getEntries()->getEntryById($entryId, $siteId);

            if (!$entry) {
                throw new NotFoundHttpException('Entry not found');
            }

            $params = ['entryId' => $entryId, 'siteId' => $entry->siteId];
        } else if ($draftId !== null) {
            $entry = Entry::find()
                ->draftId($draftId)
                ->siteId($siteId)
                ->anyStatus()
                ->one();

            if (!$entry) {
                throw new NotFoundHttpException('Entry draft not found');
            }

            $params = ['draftId' => $draftId];
        } else if ($revisionId !== null) {
            $entry = Entry::find()
                ->revisionId($revisionId)
                ->siteId($siteId)
                ->anyStatus()
                ->one();

            if (!$entry) {
                throw new NotFoundHttpException('Entry revision not found');
            }

            $params = ['revisionId' => $revisionId];
        } else {
            throw new NotFoundHttpException('Entry not found');
        }

        // Make sure they have permission to be viewing this entry
        $this->enforceEditEntryPermissions($entry);

        // Make sure the entry actually can be viewed
        if (!Craft::$app->getSections()->isSectionTemplateValid($entry->getSection(), $entry->siteId)) {
            throw new ServerErrorHttpException('Section not configured properly');
        }

        // Create the token and redirect to the entry URL with the token in place
        $token = Craft::$app->getTokens()->createToken([
            'entries/view-shared-entry',
            $params
        ]);

        if ($token === false) {
            throw new Exception('There was a problem generating the token.');
        }

        $url = UrlHelper::urlWithToken($entry->getUrl(), $token);

        return Craft::$app->getResponse()->redirect($url);
    }

    /**
     * Shows an entry/draft/version based on a token.
     *
     * @param int|null $entryId
     * @param int|null $siteId
     * @param int|null $draftId
     * @param int|null $revisionId
     * @return Response
     * @throws NotFoundHttpException if the requested entry cannot be found
     */
    public function actionViewSharedEntry(int $entryId = null, int $siteId = null, int $draftId = null, int $revisionId = null): Response
    {
        $this->requireToken();

        if ($entryId !== null) {
            $entry = Craft::$app->getEntries()->getEntryById($entryId, $siteId);
        } else if ($draftId !== null) {
            $entry = Entry::find()
                ->draftId($draftId)
                ->siteId($siteId)
                ->anyStatus()
                ->one();
        } else if ($revisionId !== null) {
            $entry = Entry::find()
                ->revisionId($revisionId)
                ->siteId($siteId)
                ->anyStatus()
                ->one();
        }

        if (empty($entry)) {
            throw new NotFoundHttpException('Entry not found');
        }

        return $this->_showEntry($entry);
    }

    // Private Methods
    // =========================================================================

    /**
     * Preps entry edit variables.
     *
     * @param array &$variables
     * @throws NotFoundHttpException if the requested section or entry cannot be found
     * @throws ForbiddenHttpException if the user is not permitted to edit content in the requested site
     */
    private function _prepEditEntryVariables(array &$variables)
    {
        $request = Craft::$app->getRequest();

        // Get the section
        // ---------------------------------------------------------------------

        if (!empty($variables['sectionHandle'])) {
            $variables['section'] = Craft::$app->getSections()->getSectionByHandle($variables['sectionHandle']);
        } else if (!empty($variables['sectionId'])) {
            $variables['section'] = Craft::$app->getSections()->getSectionById($variables['sectionId']);
        }

        if (empty($variables['section'])) {
            throw new NotFoundHttpException('Section not found');
        }

        // Get the site
        // ---------------------------------------------------------------------

        $variables['siteIds'] = $this->editableSiteIds($variables['section']);

        if (empty($variables['site'])) {
            /** @noinspection PhpUnhandledExceptionInspection */
            $variables['site'] = Craft::$app->getSites()->getCurrentSite();

            if (!in_array($variables['site']->id, $variables['siteIds'], false)) {
                $variables['site'] = Craft::$app->getSites()->getSiteById($variables['siteIds'][0]);
            }

            $site = $variables['site'];
        } else {
            // Make sure they were requesting a valid site
            /** @var Site $site */
            $site = $variables['site'];
            if (!in_array($site->id, $variables['siteIds'], false)) {
                throw new ForbiddenHttpException('User not permitted to edit content in this site');
            }
        }

        // Get the entry
        // ---------------------------------------------------------------------

        if (empty($variables['entry'])) {
            if (empty($variables['entryId'])) {
                throw new BadRequestHttpException('Request missing required entryId param');
            }

            // Get the structure ID
            $structureId = (new Query())
                ->select(['sections.structureId'])
                ->from(['{{%entries}} entries'])
                ->innerJoin('{{%sections}} sections', '[[sections.id]] = [[entries.sectionId]]')
                ->where(['entries.id' => $variables['entryId']])
                ->scalar();

            if (!empty($variables['draftId'])) {
                $variables['entry'] = Entry::find()
                    ->draftId($variables['draftId'])
                    ->structureId($structureId)
                    ->siteId($site->id)
                    ->anyStatus()
                    ->one();
            } else if (!empty($variables['revisionId'])) {
                $variables['entry'] = Entry::find()
                    ->revisionId($variables['revisionId'])
                    ->structureId($structureId)
                    ->structureId($structureId)
                    ->siteId($site->id)
                    ->anyStatus()
                    ->one();
            } else {
                $variables['entry'] = Entry::find()
                    ->id($variables['entryId'])
                    ->structureId($structureId)
                    ->siteId($site->id)
                    ->anyStatus()
                    ->one();
            }

            if (!$variables['entry']) {
                throw new NotFoundHttpException('Entry not found');
            }
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
            count($variables['entry']->getSupportedSites()) > 1
        );

        // Get the entry type
        // ---------------------------------------------------------------------

        // Override the entry type?
        $typeId = $request->getParam('typeId');

        if (!$typeId) {
            // Default to the section's first entry type
            $typeId = $variables['entry']->typeId ?? $variables['section']->getEntryTypes()[0]->id;
        }

        $variables['entry']->typeId = $typeId;
        $variables['entryType'] = $variables['entry']->getType();

        // Prevent the last entry type's field layout from being used
        $variables['entry']->fieldLayoutId = null;

        // Define the content tabs
        // ---------------------------------------------------------------------

        $variables['tabs'] = [];

        foreach ($variables['entryType']->getFieldLayout()->getTabs() as $index => $tab) {
            // Do any of the fields on this tab have errors?
            $hasErrors = false;

            if ($variables['entry']->hasErrors()) {
                foreach ($tab->getFields() as $field) {
                    /** @var Field $field */
                    if ($hasErrors = $variables['entry']->hasErrors($field->handle . '.*')) {
                        break;
                    }
                }
            }

            $variables['tabs'][] = [
                'label' => Craft::t('site', $tab->name),
                'url' => '#' . $tab->getHtmlId(),
                'class' => $hasErrors ? 'error' : null
            ];
        }
    }

    /**
     * Fetches or creates an Entry.
     *
     * @return Entry
     * @throws NotFoundHttpException if the requested entry cannot be found
     */
    private function _getEntryModel(): Entry
    {
        $request = Craft::$app->getRequest();
        $entryId = $request->getRequiredBodyParam('entryId');
        $siteId = $request->getBodyParam('siteId');

        if ($entryId) {
            $entry = Craft::$app->getEntries()->getEntryById($entryId, $siteId);

            if (!$entry) {
                throw new NotFoundHttpException('Entry not found');
            }
        } else {
            $entry = new Entry();
            $entry->sectionId = $request->getRequiredBodyParam('sectionId');

            if ($siteId) {
                $entry->siteId = $siteId;
            }
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
        $request = Craft::$app->getRequest();

        // Set the entry attributes, defaulting to the existing values for whatever is missing from the post data
        $entry->typeId = $request->getBodyParam('typeId', $entry->typeId);
        $entry->slug = $request->getBodyParam('slug', $entry->slug);
        if (($postDate = $request->getBodyParam('postDate')) !== null) {
            $entry->postDate = DateTimeHelper::toDateTime($postDate) ?: null;
        }
        if (($expiryDate = $request->getBodyParam('expiryDate')) !== null) {
            $entry->expiryDate = DateTimeHelper::toDateTime($expiryDate) ?: null;
        }
        $entry->enabled = (bool)$request->getBodyParam('enabled', $entry->enabled);
        $entry->enabledForSite = (bool)$request->getBodyParam('enabledForSite', $entry->enabledForSite);
        $entry->title = $request->getBodyParam('title', $entry->title);

        if (!$entry->typeId) {
            // Default to the section's first entry type
            $entry->typeId = $entry->getSection()->getEntryTypes()[0]->id;
        }

        // Prevent the last entry type's field layout from being used
        $entry->fieldLayoutId = null;

        $fieldsLocation = $request->getParam('fieldsLocation', 'fields');
        $entry->setFieldValuesFromRequest($fieldsLocation);

        // Author
        $authorId = $request->getBodyParam('author', ($entry->authorId ?: Craft::$app->getUser()->getIdentity()->id));

        if (is_array($authorId)) {
            $authorId = $authorId[0] ?? null;
        }

        $entry->authorId = $authorId;

        // Parent
        if (($parentId = $request->getBodyParam('parentId')) !== null) {
            if (is_array($parentId)) {
                $parentId = reset($parentId) ?: '';
            }

            $entry->newParentId = $parentId ?: '';
        }

        // Revision notes
        $entry->setRevisionNotes($request->getBodyParam('revisionNotes'));
    }

    /**
     * Displays an entry.
     *
     * @param Entry $entry
     * @return Response
     * @throws ServerErrorHttpException if the entry doesn't have a URL for the site it's configured with, or if the entry's site ID is invalid
     */
    private function _showEntry(Entry $entry): Response
    {
        $sectionSiteSettings = $entry->getSection()->getSiteSettings();

        if (!isset($sectionSiteSettings[$entry->siteId]) || !$sectionSiteSettings[$entry->siteId]->hasUrls) {
            throw new ServerErrorHttpException('The entry ' . $entry->id . ' doesn’t have a URL for the site ' . $entry->siteId . '.');
        }

        $site = Craft::$app->getSites()->getSiteById($entry->siteId);

        if (!$site) {
            throw new ServerErrorHttpException('Invalid site ID: ' . $entry->siteId);
        }

        Craft::$app->language = $site->language;
        Craft::$app->set('locale', Craft::$app->getI18n()->getLocaleById($site->language));

        if (!$entry->postDate) {
            $entry->postDate = new DateTime();
        }

        // Have this entry override any freshly queried entries with the same ID/site ID
        Craft::$app->getElements()->setPlaceholderElement($entry);

        $this->getView()->getTwig()->disableStrictVariables();

        return $this->renderTemplate($sectionSiteSettings[$entry->siteId]->template, [
            'entry' => $entry
        ]);
    }
}
