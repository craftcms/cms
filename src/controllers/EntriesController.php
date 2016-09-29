<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\controllers;

use Craft;
use craft\app\dates\DateTime;
use craft\app\elements\User;
use craft\app\helpers\ArrayHelper;
use craft\app\helpers\DateTimeHelper;
use craft\app\helpers\Json;
use craft\app\helpers\Url;
use craft\app\elements\Entry;
use craft\app\models\EntryDraft;
use craft\app\models\EntryVersion;
use craft\app\models\Section;
use craft\app\models\Site;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\web\ServerErrorHttpException;

/**
 * The EntriesController class is a controller that handles various entry related tasks such as retrieving, saving,
 * swapping between entry types, previewing, deleting and sharing entries.
 *
 * Note that all actions in the controller except [[actionViewSharedEntry]] require an authenticated Craft session
 * via [[Controller::allowAnonymous]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class EntriesController extends BaseEntriesController
{
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
     * @param string  $sectionHandle The section’s handle
     * @param integer $entryId       The entry’s ID, if editing an existing entry.
     * @param integer $draftId       The entry draft’s ID, if editing an existing draft.
     * @param integer $versionId     The entry version’s ID, if editing an existing version.
     * @param integer $siteHandle    The site handle, if specified.
     * @param Entry   $entry         The entry being edited, if there were any validation errors.
     *
     * @return string The rendering result
     * @throws NotFoundHttpException if the requested site handle is invalid
     */
    public function actionEditEntry($sectionHandle, $entryId = null, $draftId = null, $versionId = null, $siteHandle = null, Entry $entry = null)
    {
        if ($siteHandle) {
            $site = Craft::$app->getSites()->getSiteByHandle($siteHandle);

            if (!$site) {
                throw new NotFoundHttpException('Invalid site handle: '.$siteHandle);
            }
        } else {
            $site = Craft::$app->getSites()->currentSite;
        }

        $variables = [
            'sectionHandle' => $sectionHandle,
            'entryId' => $entryId,
            'draftId' => $draftId,
            'versionId' => $versionId,
            'site' => $site,
            'entry' => $entry
        ];

        $this->_prepEditEntryVariables($variables);

        /** @var Entry $entry */
        $entry = $variables['entry'];
        /** @var Section $section */
        $section = $variables['section'];

        // Make sure they have permission to edit this entry
        $this->enforceEditEntryPermissions($entry);

        $currentUser = Craft::$app->getUser()->getIdentity();

        $variables['permissionSuffix'] = ':'.$entry->sectionId;

        if (Craft::$app->getEdition() == Craft::Pro && $section->type != Section::TYPE_SINGLE) {
            // Author selector variables
            // ---------------------------------------------------------------------

            $variables['userElementType'] = User::class;

            $authorPermission = 'editEntries'.$variables['permissionSuffix'];

            $variables['authorOptionCriteria'] = [
                'can' => $authorPermission,
            ];

            $variables['author'] = $entry->getAuthor();

            if (!$variables['author']) {
                // Default to the current user
                $variables['author'] = $currentUser;
            }
        }

        // Parent Entry selector variables
        // ---------------------------------------------------------------------

        if (
            $section->type == Section::TYPE_STRUCTURE &&
            $section->maxLevels != 1
        ) {
            $variables['elementType'] = Entry::class;

            $variables['parentOptionCriteria'] = [
                'siteId' => $site->id,
                'sectionId' => $section->id,
                'status' => null,
                'enabledForSite' => false,
            ];

            if ($section->maxLevels) {
                $variables['parentOptionCriteria']['level'] = '< '.$section->maxLevels;
            }

            if ($entry->id) {
                // Prevent the current entry, or any of its descendants, from being options
                $excludeIds = Entry::find()
                    ->descendantOf($entry)
                    ->status(null)
                    ->enabledForSite(false)
                    ->ids();

                $excludeIds[] = $entry->id;
                $variables['parentOptionCriteria']['where'] = [
                    'not in',
                    'elements.id',
                    $excludeIds
                ];
            }

            // Get the initially selected parent
            $parentId = Craft::$app->getRequest()->getParam('parentId');

            if ($parentId === null && $entry->id) {
                // Is it already set on the model (e.g. if we're loading a draft)?
                if ($entry->newParentId) {
                    $parentId = $entry->newParentId;
                } else {
                    $parentIds = $entry->getAncestors(1)->status(null)->enabledForSite(false)->ids();

                    if ($parentIds) {
                        $parentId = $parentIds[0];
                    }
                }
            }

            if ($parentId) {
                $variables['parent'] = Craft::$app->getEntries()->getEntryById($parentId, $site->id);
            }
        }

        // Enabled sites
        // ---------------------------------------------------------------------

        if (Craft::$app->getIsMultiSite()) {
            if ($entry->id) {
                $variables['enabledSiteIds'] = Craft::$app->getElements()->getEnabledSiteIdsForElement($entry->id);
            } else {
                // Set defaults based on the section settings
                $variables['enabledSiteIds'] = [];

                foreach ($section->getSiteSettings() as $siteSettings) {
                    if ($siteSettings->enabledByDefault) {
                        $variables['enabledSiteIds'][] = $siteSettings->siteId;
                    }
                }
            }
        }

        // Other variables
        // ---------------------------------------------------------------------

        // Page title w/ revision label
        switch ($entry::className()) {
            case EntryDraft::class: {
                /** @var EntryDraft $entry */
                $variables['revisionLabel'] = $entry->name;
                break;
            }

            case EntryVersion::class: {
                /** @var EntryVersion $entry */
                $variables['revisionLabel'] = Craft::t('app', 'Version {num}', ['num' => $entry->num]);
                break;
            }

            default: {
                $variables['revisionLabel'] = Craft::t('app', 'Current');
            }
        }

        if (!$entry->id) {
            $variables['title'] = Craft::t('app', 'Create a new entry');
        } else {
            $variables['docTitle'] = $variables['title'] = $entry->title;

            if ($entry::className() != Entry::class) {
                $variables['docTitle'] .= ' ('.$variables['revisionLabel'].')';
            }
        }

        // Breadcrumbs
        $variables['crumbs'] = [
            [
                'label' => Craft::t('app', 'Entries'),
                'url' => Url::getUrl('entries')
            ]
        ];

        if ($section->type == Section::TYPE_SINGLE) {
            $variables['crumbs'][] = [
                'label' => Craft::t('app', 'Singles'),
                'url' => Url::getUrl('entries/singles')
            ];
        } else {
            $variables['crumbs'][] = [
                'label' => Craft::t('site', $section->name),
                'url' => Url::getUrl('entries/'.$section->handle)
            ];

            if ($section->type == Section::TYPE_STRUCTURE) {
                /** @var Entry $ancestor */
                foreach ($entry->getAncestors() as $ancestor) {
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

            Craft::$app->getView()->registerJsResource('js/EntryTypeSwitcher.js');
            Craft::$app->getView()->registerJs('new Craft.EntryTypeSwitcher();');
        } else {
            $variables['showEntryTypes'] = false;
        }

        // Enable Live Preview?
        if (!Craft::$app->getRequest()->isMobileBrowser(true) && Craft::$app->getSections()->isSectionTemplateValid($section, $entry->siteId)) {
            Craft::$app->getView()->registerJs('Craft.LivePreview.init('.Json::encode([
                    'fields' => '#title-field, #fields > div > div > .field',
                    'extraFields' => '#settings',
                    'previewUrl' => $entry->getUrl(),
                    'previewAction' => 'entries/preview-entry',
                    'previewParams' => [
                        'sectionId' => $section->id,
                        'entryId' => $entry->id,
                        'siteId' => $entry->siteId,
                        'versionId' => ($entry::className() == EntryVersion::class ? $entry->versionId : null),
                    ]
                ]).');');

            $variables['showPreviewBtn'] = true;

            // Should we show the Share button too?
            if ($entry->id) {
                $className = $entry::className();

                // If we're looking at the live version of an entry, just use
                // the entry's main URL as its share URL
                if ($className == Entry::class && $entry->getStatus() == Entry::STATUS_LIVE) {
                    $variables['shareUrl'] = $entry->getUrl();
                } else {
                    switch ($className) {
                        case EntryDraft::class: {
                            /** @var EntryDraft $entry */
                            $shareParams = ['draftId' => $entry->draftId];
                            break;
                        }
                        case EntryVersion::class: {
                            /** @var EntryVersion $entry */
                            $shareParams = ['versionId' => $entry->versionId];
                            break;
                        }
                        default: {
                            $shareParams = [
                                'entryId' => $entry->id,
                                'siteId' => $entry->siteId
                            ];
                            break;
                        }
                    }

                    $variables['shareUrl'] = Url::getActionUrl('entries/share-entry', $shareParams);
                }
            }
        } else {
            $variables['showPreviewBtn'] = false;
        }

        // Set the base CP edit URL

        // Can't just use the entry's getCpEditUrl() because that might include the site handle when we don't want it
        $variables['baseCpEditUrl'] = 'entries/'.$section->handle.'/{id}-{slug}';

        // Set the "Continue Editing" URL
        $variables['continueEditingUrl'] = $variables['baseCpEditUrl'].
            (isset($variables['draftId']) ? '/drafts/'.$variables['draftId'] : '').
            (Craft::$app->getIsMultiSite() && Craft::$app->getSites()->currentSite->id != $site->id ? '/'.$site->handle : '');

        // Can the user delete the entry?
        $variables['canDeleteEntry'] = $entry->id && (
                ($entry->authorId == $currentUser->id && $currentUser->can('deleteEntries'.$variables['permissionSuffix'])) ||
                ($entry->authorId != $currentUser->id && $currentUser->can('deletePeerEntries'.$variables['permissionSuffix']))
            );

        // Full page form variables
        $variables['fullPageForm'] = true;
        $variables['saveShortcutRedirect'] = $variables['continueEditingUrl'];

        // Include translations
        Craft::$app->getView()->registerTranslations('app', [
            'Live Preview',
        ]);

        // Render the template!
        Craft::$app->getView()->registerCssResource('css/entry.css');

        return $this->renderTemplate('entries/_edit', $variables);
    }

    /**
     * Switches between two entry types.
     *
     * @return Response
     */
    public function actionSwitchEntryType()
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $entry = $this->_getEntryModel();
        $this->enforceEditEntryPermissions($entry);
        $this->_populateEntryModel($entry);

        $variables['sectionId'] = $entry->sectionId;
        $variables['entry'] = $entry;
        $variables['showEntryTypes'] = true;

        $this->_prepEditEntryVariables($variables);

        $paneHtml = Craft::$app->getView()->renderTemplate('_includes/tabs',
                $variables).
            Craft::$app->getView()->renderTemplate('entries/_fields', $variables);

        $view = Craft::$app->getView();

        return $this->asJson([
            'paneHtml' => $paneHtml,
            'headHtml' => $view->getHeadHtml(),
            'footHtml' => $view->getBodyHtml(),
        ]);
    }

    /**
     * Previews an entry.
     *
     * @return string
     * @throws NotFoundHttpException if the requested entry version cannot be found
     */
    public function actionPreviewEntry()
    {
        $this->requirePostRequest();

        // Are we previewing a version?
        $versionId = Craft::$app->getRequest()->getBodyParam('versionId');

        if ($versionId) {
            $entry = Craft::$app->getEntryRevisions()->getVersionById($versionId);

            if (!$entry) {
                throw new NotFoundHttpException('Entry version not found');
            }

            $this->enforceEditEntryPermissions($entry);
        } else {
            $entry = $this->_getEntryModel();
            $this->enforceEditEntryPermissions($entry);

            // Set the language to the user's preferred language so DateFormatter returns the right format
            Craft::$app->language = Craft::$app->getTargetLanguage(true);

            $this->_populateEntryModel($entry);
        }

        return $this->_showEntry($entry);
    }

    /**
     * Saves an entry.
     *
     * @return Response|null
     */
    public function actionSaveEntry()
    {
        $this->requirePostRequest();

        $entry = $this->_getEntryModel();
        $request = Craft::$app->getRequest();

        // Permission enforcement
        $this->enforceEditEntryPermissions($entry);
        $currentUser = Craft::$app->getUser()->getIdentity();

        if ($entry->id) {
            // Is this another user's entry (and it's not a Single)?
            if (
                $entry->authorId != $currentUser->id &&
                $entry->getSection()->type != Section::TYPE_SINGLE
            ) {
                if ($entry->enabled) {
                    // Make sure they have permission to make live changes to those
                    $this->requirePermission('publishPeerEntries:'.$entry->sectionId);
                }
            }
        }

        // Populate the entry with post data
        $this->_populateEntryModel($entry);

        // Even more permission enforcement
        if ($entry->enabled) {
            if ($entry->id) {
                $this->requirePermission('publishEntries:'.$entry->sectionId);
            } else if (!$currentUser->can('publishEntries:'.$entry->sectionId)) {
                $entry->enabled = false;
            }
        }

        // Save the entry (finally!)
        if (Craft::$app->getEntries()->saveEntry($entry)) {
            if ($request->getAcceptsJson()) {
                $return['success'] = true;
                $return['id'] = $entry->id;
                $return['title'] = $entry->title;

                if (!$request->getIsConsoleRequest() && $request->getIsCpRequest()) {
                    $return['cpEditUrl'] = $entry->getCpEditUrl();
                }

                $return['authorUsername'] = $entry->getAuthor()->username;
                $return['dateCreated'] = DateTimeHelper::toIso8601($entry->dateCreated);
                $return['dateUpdated'] = DateTimeHelper::toIso8601($entry->dateUpdated);
                $return['postDate'] = ($entry->postDate ? DateTimeHelper::toIso8601($entry->postDate) : null);

                return $this->asJson($return);
            }

            Craft::$app->getSession()->setNotice(Craft::t('app', 'Entry saved.'));

            return $this->redirectToPostedUrl($entry);
        }

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

    /**
     * Deletes an entry.
     *
     * @return Response|null
     * @throws NotFoundHttpException if the requested entry cannot be found
     */
    public function actionDeleteEntry()
    {
        $this->requirePostRequest();

        $entryId = Craft::$app->getRequest()->getRequiredBodyParam('entryId');
        $siteId = Craft::$app->getRequest()->getBodyParam('siteId');
        $entry = Craft::$app->getEntries()->getEntryById($entryId, $siteId);

        if (!$entry) {
            throw new NotFoundHttpException('Entry not found');
        }

        $currentUser = Craft::$app->getUser()->getIdentity();

        if ($entry->authorId == $currentUser->id) {
            $this->requirePermission('deleteEntries:'.$entry->sectionId);
        } else {
            $this->requirePermission('deletePeerEntries:'.$entry->sectionId);
        }

        if (Craft::$app->getEntries()->deleteEntry($entry)) {
            if (Craft::$app->getRequest()->getAcceptsJson()) {
                return $this->asJson(['success' => true]);
            }

            Craft::$app->getSession()->setNotice(Craft::t('app', 'Entry deleted.'));

            return $this->redirectToPostedUrl($entry);
        }

        if (Craft::$app->getRequest()->getAcceptsJson()) {
            return $this->asJson(['success' => false]);
        }

        Craft::$app->getSession()->setError(Craft::t('app', 'Couldn’t delete entry.'));

        // Send the entry back to the template
        Craft::$app->getUrlManager()->setRouteParams([
            'entry' => $entry
        ]);


        return null;
    }

    /**
     * Redirects the client to a URL for viewing an entry/draft/version on the front end.
     *
     * @param integer $entryId
     * @param integer $siteId
     * @param integer $draftId
     * @param integer $versionId
     *
     * @return Response
     * @throws NotFoundHttpException if the requested entry/revision cannot be found
     * @throws ServerErrorHttpException if the section is not configured properly
     */
    public function actionShareEntry($entryId = null, $siteId = null, $draftId = null, $versionId = null)
    {
        if ($entryId) {
            $entry = Craft::$app->getEntries()->getEntryById($entryId, $siteId);

            if (!$entry) {
                throw new NotFoundHttpException('Entry not found');
            }

            $params = ['entryId' => $entryId, 'siteId' => $entry->siteId];
        } else if ($draftId) {
            $entry = Craft::$app->getEntryRevisions()->getDraftById($draftId);

            if (!$entry) {
                throw new NotFoundHttpException('Entry draft not found');
            }

            $params = ['draftId' => $draftId];
        } else if ($versionId) {
            $entry = Craft::$app->getEntryRevisions()->getVersionById($versionId);

            if (!$entry) {
                throw new NotFoundHttpException('Entry version not found');
            }

            $params = ['versionId' => $versionId];
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
        $url = Url::getUrlWithToken($entry->getUrl(), $token);

        return Craft::$app->getResponse()->redirect($url);
    }

    /**
     * Shows an entry/draft/version based on a token.
     *
     * @param integer $entryId
     * @param integer $siteId
     * @param integer $draftId
     * @param integer $versionId
     *
     * @return Response
     * @throws NotFoundHttpException if the requested category cannot be found
     */
    public function actionViewSharedEntry($entryId = null, $siteId = null, $draftId = null, $versionId = null)
    {
        $this->requireToken();

        if ($entryId) {
            $entry = Craft::$app->getEntries()->getEntryById($entryId, $siteId);
        } else if ($draftId) {
            $entry = Craft::$app->getEntryRevisions()->getDraftById($draftId);
        } else if ($versionId) {
            $entry = Craft::$app->getEntryRevisions()->getVersionById($versionId);
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
     *
     * @return void
     * @throws NotFoundHttpException if the requested section or entry cannot be found
     * @throws ForbiddenHttpException if the user is not permitted to edit content in the requested site
     */
    private function _prepEditEntryVariables(&$variables)
    {
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

        if (Craft::$app->getIsMultiSite()) {
            // Only use the sites that the user has access to
            $sectionSiteIds = array_keys($variables['section']->getSiteSettings());
            $editableSiteIds = Craft::$app->getSites()->getEditableSiteIds();
            $variables['siteIds'] = array_merge(array_intersect($sectionSiteIds, $editableSiteIds));
        } else {
            $variables['siteIds'] = [Craft::$app->getSites()->getPrimarySite()->id];
        }

        if (!$variables['siteIds']) {
            throw new ForbiddenHttpException('User not permitted to edit content in any sites supported by this section');
        }

        if (empty($variables['site'])) {
            $variables['site'] = Craft::$app->getSites()->currentSite;

            if (!in_array($variables['site']->id, $variables['siteIds'])) {
                $variables['site'] = Craft::$app->getSites()->getSiteById($variables['siteIds'][0]);
            }

            $site = $variables['site'];
        } else {
            // Make sure they were requesting a valid site
            /** @var Site $site */
            $site = $variables['site'];
            if (!in_array($site->id, $variables['siteIds'])) {
                throw new ForbiddenHttpException('User not permitted to edit content in this site');
            }
        }

        // Get the entry
        // ---------------------------------------------------------------------

        if (empty($variables['entry'])) {
            if (!empty($variables['entryId'])) {
                if (!empty($variables['draftId'])) {
                    $variables['entry'] = Craft::$app->getEntryRevisions()->getDraftById($variables['draftId']);
                } else if (!empty($variables['versionId'])) {
                    $variables['entry'] = Craft::$app->getEntryRevisions()->getVersionById($variables['versionId']);
                } else {
                    $variables['entry'] = Craft::$app->getEntries()->getEntryById($variables['entryId'], $site->id);

                    if ($variables['entry']) {
                        $versions = Craft::$app->getEntryRevisions()->getVersionsByEntryId($variables['entryId'], $site->id, 1, true);

                        if (isset($versions[0])) {
                            $variables['entry']->revisionNotes = $versions[0]->revisionNotes;
                        }
                    }
                }

                if (!$variables['entry']) {
                    throw new NotFoundHttpException('Entry not found');
                }
            } else {
                $variables['entry'] = new Entry();
                $variables['entry']->sectionId = $variables['section']->id;
                $variables['entry']->authorId = Craft::$app->getUser()->getIdentity()->id;
                $variables['entry']->enabled = true;
                $variables['entry']->siteId = $site->id;

                if (Craft::$app->getIsMultiSite()) {
                    // Set the default site status based on the section's settings
                    foreach ($variables['section']->getSiteSettings() as $siteSettings) {
                        if ($siteSettings->siteId == $variables['entry']->siteId) {
                            $variables['entry']->enabledForSite = $siteSettings->enabledByDefault;
                            break;
                        }
                    }
                } else {
                    // Set the default entry status based on the section's settings
                    foreach ($variables['section']->getSiteSettings() as $siteSettings) {
                        if (!$siteSettings->enabledByDefault) {
                            $variables['entry']->enabled = false;
                        }
                        break;
                    }
                }
            }
        }

        // Get the entry type
        // ---------------------------------------------------------------------

        // Override the entry type?
        $typeId = Craft::$app->getRequest()->getParam('typeId');

        if (!$typeId) {
            // Default to the section's first entry type
            $typeId = ArrayHelper::getFirstKey($variables['section']->getEntryTypes('id'));
        }

        $variables['entry']->typeId = $typeId;

        $variables['entryType'] = $variables['entry']->getType();

        // Define the content tabs
        // ---------------------------------------------------------------------

        $variables['tabs'] = [];

        foreach ($variables['entryType']->getFieldLayout()->getTabs() as $index => $tab) {
            // Do any of the fields on this tab have errors?
            $hasErrors = false;

            if ($variables['entry']->hasErrors()) {
                foreach ($tab->getFields() as $field) {
                    if ($variables['entry']->getErrors($field->handle)) {
                        $hasErrors = true;
                        break;
                    }
                }
            }

            $variables['tabs'][] = [
                'label' => Craft::t('site', $tab->name),
                'url' => '#tab'.($index + 1),
                'class' => ($hasErrors ? 'error' : null)
            ];
        }
    }

    /**
     * Fetches or creates an Entry.
     *
     * @return Entry
     * @throws NotFoundHttpException if the requested entry cannot be found
     */
    private function _getEntryModel()
    {
        $entryId = Craft::$app->getRequest()->getBodyParam('entryId');
        $siteId = Craft::$app->getRequest()->getBodyParam('siteId');

        if ($entryId) {
            $entry = Craft::$app->getEntries()->getEntryById($entryId, $siteId);

            if (!$entry) {
                throw new NotFoundHttpException('Entry not found');
            }
        } else {
            $entry = new Entry();
            $entry->sectionId = Craft::$app->getRequest()->getRequiredBodyParam('sectionId');

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
     *
     * @return void
     */
    private function _populateEntryModel(Entry $entry)
    {
        // Set the entry attributes, defaulting to the existing values for whatever is missing from the post data
        $entry->typeId = Craft::$app->getRequest()->getBodyParam('typeId', $entry->typeId);
        $entry->slug = Craft::$app->getRequest()->getBodyParam('slug', $entry->slug);
        $entry->postDate = (($postDate = DateTimeHelper::toDateTime(Craft::$app->getRequest()->getBodyParam('postDate'))) !== false ? $postDate : $entry->postDate);
        $entry->expiryDate = (($expiryDate = DateTimeHelper::toDateTime(Craft::$app->getRequest()->getBodyParam('expiryDate'))) !== false ? $expiryDate : null);
        $entry->enabled = (bool)Craft::$app->getRequest()->getBodyParam('enabled', $entry->enabled);
        $entry->enabledForSite = (bool)Craft::$app->getRequest()->getBodyParam('enabledForSite', $entry->enabledForSite);
        $entry->title = Craft::$app->getRequest()->getBodyParam('title', $entry->title);

        if (!$entry->typeId) {
            // Default to the section's first entry type
            $entry->typeId = ArrayHelper::getFirstKey($entry->getSection()->getEntryTypes('id'));
        }

        $fieldsLocation = Craft::$app->getRequest()->getParam('fieldsLocation', 'fields');
        $entry->setFieldValuesFromPost($fieldsLocation);

        // Author
        $authorId = Craft::$app->getRequest()->getBodyParam('author', ($entry->authorId ? $entry->authorId : Craft::$app->getUser()->getIdentity()->id));

        if (is_array($authorId)) {
            $authorId = isset($authorId[0]) ? $authorId[0] : null;
        }

        $entry->authorId = $authorId;

        // Parent
        $parentId = Craft::$app->getRequest()->getBodyParam('parentId');

        if (is_array($parentId)) {
            $parentId = isset($parentId[0]) ? $parentId[0] : null;
        }

        $entry->newParentId = $parentId;

        // Revision notes
        $entry->revisionNotes = Craft::$app->getRequest()->getBodyParam('revisionNotes');
    }

    /**
     * Displays an entry.
     *
     * @param Entry $entry
     *
     * @return string The rendering result
     * @throws ServerErrorHttpException if the entry doesn't have a URL for the site it's configured with, or if the entry's site ID is invalid
     */
    private function _showEntry(Entry $entry)
    {
        $sectionSiteSettings = $entry->getSection()->getSiteSettings();

        if (!isset($sectionSiteSettings[$entry->siteId]) || !$sectionSiteSettings[$entry->siteId]->hasUrls) {
            throw new ServerErrorHttpException('The entry '.$entry->id.' doesn\'t have a URL for the site '.$entry->siteId.'.');
        }

        $site = Craft::$app->getSites()->getSiteById($entry->siteId);

        if (!$site) {
            throw new ServerErrorHttpException('Invalid site ID: '.$entry->siteId);
        }

        Craft::$app->language = $site->language;

        if (!$entry->postDate) {
            $entry->postDate = new DateTime();
        }

        // Have this entry override any freshly queried entries with the same ID/site ID
        Craft::$app->getElements()->setPlaceholderElement($entry);

        Craft::$app->getView()->getTwig()->disableStrictVariables();

        return $this->renderTemplate($sectionSiteSettings[$entry->siteId]->template, [
            'entry' => $entry
        ]);
    }
}
