<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use Craft;
use craft\base\Element;
use craft\base\ElementInterface;
use craft\behaviors\DraftBehavior;
use craft\elements\Entry;
use craft\errors\InvalidElementException;
use craft\helpers\ArrayHelper;
use craft\helpers\DateTimeHelper;
use craft\helpers\ElementHelper;
use craft\helpers\UrlHelper;
use craft\models\Section;
use craft\models\Section_SiteSettings;
use yii\base\Exception;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\web\ServerErrorHttpException;

/**
 * The EntryRevisionsController class is a controller that handles various entry version and draft related tasks such as
 * retrieving, saving, deleting, publishing and reverting entry drafts and versions.
 * Note that all actions in the controller require an authenticated Craft session via [[allowAnonymous]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class EntryRevisionsController extends BaseEntriesController
{
    /**
     * Creates a new entry draft and redirects the client to its edit URL
     *
     * @param string $section The section’s handle
     * @param string|null $site The site handle, if specified.
     * @return Response
     * @throws BadRequestHttpException
     */
    public function actionCreateDraft(string $section, string $site = null): Response
    {
        $sectionHandle = $section;
        $section = Craft::$app->getSections()->getSectionByHandle($sectionHandle);
        if (!$section) {
            throw new BadRequestHttpException('Invalid section handle: ' . $sectionHandle);
        }

        $editableSiteIds = $this->editableSiteIds($section);
        $sitesService = Craft::$app->getSites();

        if ($site !== null) {
            $siteHandle = $site;
            $site = $sitesService->getSiteByHandle($siteHandle);
            if (!$site) {
                throw new BadRequestHttpException('Invalid site handle: ' . $siteHandle);
            }
        }

        // If there's only one site, go with that
        if ($site === null && count($editableSiteIds) === 1) {
            $site = $sitesService->getSiteById($editableSiteIds[0]);
        }

        // If entries get propagated to all sites, it doesn't really matter which site we start with
        if ($site === null && $section->propagationMethod === Section::PROPAGATION_METHOD_ALL) {
            $site = $sitesService->getPrimarySite();
            if (!in_array($site->id, $editableSiteIds, false)) {
                $site = $sitesService->getSiteById($editableSiteIds[0]);
            }
        }

        // If we still don't know the site, give the user a chance to pick one
        if ($site === null) {
            return $this->renderTemplate('_special/sitepicker', [
                'siteIds' => $editableSiteIds,
                'baseUrl' => "entries/{$section->handle}/new",
            ]);
        }

        // Create & populate the draft
        $request = Craft::$app->getRequest();
        $entry = new Entry();
        $entry->siteId = $site->id;
        $entry->sectionId = $section->id;
        $entry->typeId = $request->getQueryParam('typeId', $section->getEntryTypes()[0]->id);
        $entry->authorId = $request->getQueryParam('authorId', Craft::$app->getUser()->getId());
        $entry->slug = ElementHelper::tempSlug();

        // Set the default status based on the section's settings
        /** @var Section_SiteSettings $siteSettings */
        $siteSettings = ArrayHelper::firstWhere($section->getSiteSettings(), 'siteId', $entry->siteId);
        if (Craft::$app->getIsMultiSite() && count($entry->getSupportedSites()) > 1) {
            $entry->enabled = true;
            $entry->enabledForSite = $siteSettings->enabledByDefault;
        } else {
            $entry->enabled = $siteSettings->enabledByDefault;
            $entry->enabledForSite = true;
        }

        // Structure parent
        if (
            $section->type === Section::TYPE_STRUCTURE &&
            (int)$section->maxLevels !== 1
        ) {
            // Get the initially selected parent
            $entry->newParentId = $request->getParam('parentId');
            if (is_array($entry->newParentId)) {
                $entry->newParentId = reset($parentId) ?: null;
            }
        }

        // Make sure the user is allowed to create this entry
        $this->enforceEditEntryPermissions($entry);

        // Save it and redirect to its edit page
        $entry->setScenario(Element::SCENARIO_ESSENTIALS);
        if (!Craft::$app->getDrafts()->saveElementAsDraft($entry, Craft::$app->getUser()->getId())) {
            throw new Exception('Unable to save entry as a draft: ' . implode(', ', $entry->getErrorSummary(true)));
        }

        return $this->redirect(UrlHelper::url($entry->getCpEditUrl(), [
            'draftId' => $entry->draftId,
            'fresh' => 1,
        ]));
    }

    /**
     * Saves a draft, or creates a new one.
     *
     * @return Response|null
     * @throws NotFoundHttpException if the requested entry draft cannot be found
     */
    public function actionSaveDraft()
    {
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();
        $elementsService = Craft::$app->getElements();

        $draftId = $request->getBodyParam('draftId');
        $entryId = $request->getBodyParam('entryId');
        $siteId = $request->getBodyParam('siteId') ?: Craft::$app->getSites()->getPrimarySite()->id;
        $fieldsLocation = $request->getParam('fieldsLocation', 'fields');

        // Are we creating a new entry too?
        if (!$draftId && !$entryId) {
            $entry = new Entry();
            $entry->siteId = $siteId;
            $entry->sectionId = $request->getBodyParam('sectionId');
            $this->_setDraftAttributesFromPost($entry);
            $this->enforceEditEntryPermissions($entry);
            $entry->setFieldValuesFromRequest($fieldsLocation);
            $entry->updateTitle();

            $enabled = $entry->enabled;
            $entry->enabled = false;

            // Manually validate 'title' since the Elements service will just give it a title automatically.
            if (!$entry->validate(['title']) || !$elementsService->saveElement($entry, false)) {
                Craft::$app->getSession()->setError(Craft::t('app', 'Couldn’t save draft.'));
                Craft::$app->getUrlManager()->setRouteParams([
                    'entry' => $entry,
                ]);
                return null;
            }

            $entry->enabled = $enabled;
            /** @var Entry|DraftBehavior $draft */
            $draft = Craft::$app->getDrafts()->createDraft($entry, Craft::$app->getUser()->getId());
        } else {
            $transaction = null;

            if ($draftId) {
                $draft = Entry::find()
                    ->draftId($draftId)
                    ->siteId($siteId)
                    ->anyStatus()
                    ->one();
                if (!$draft) {
                    throw new NotFoundHttpException('Entry draft not found');
                }
                $this->enforceEditEntryPermissions($draft);

                // Draft meta
                /** @var Entry|DraftBehavior $draft */
                $draft->draftName = $request->getBodyParam('draftName');
                $draft->draftNotes = $request->getBodyParam('draftNotes');
            } else {
                $entry = Entry::find()
                    ->id($entryId)
                    ->siteId($siteId)
                    ->anyStatus()
                    ->one();
                if (!$entry) {
                    throw new NotFoundHttpException('Entry not found');
                }
                $this->enforceEditEntryPermissions($entry);

                // Create the draft in a transaction so we can undo it if something goes wrong
                $transaction = Craft::$app->getDb()->beginTransaction();

                /** @var Entry|DraftBehavior $draft */
                $draft = Craft::$app->getDrafts()->createDraft($entry, Craft::$app->getUser()->getId());
            }

            $this->_setDraftAttributesFromPost($draft);
            $draft->setFieldValuesFromRequest($fieldsLocation);
            $draft->updateTitle();
            $draft->setScenario(Element::SCENARIO_ESSENTIALS);

            if ($draft->getIsUnsavedDraft() && $request->getBodyParam('propagateAll')) {
                $draft->propagateAll = true;
            }

            if (!$elementsService->saveElement($draft)) {
                if ($transaction !== null) {
                    $transaction->rollBack();
                }

                if ($request->getAcceptsJson()) {
                    return $this->asJson([
                        'errors' => $draft->getErrorSummary(true),
                    ]);
                }

                Craft::$app->getSession()->setError(Craft::t('app', 'Couldn’t save draft.'));
                Craft::$app->getUrlManager()->setRouteParams([
                    'entry' => $draft,
                ]);
                return null;
            }

            if ($transaction !== null) {
                $transaction->commit();
            }
        }

        // Make sure the user is authorized to preview the draft
        Craft::$app->getSession()->authorize('previewDraft:' . $draft->draftId);

        /** @var ElementInterface|DraftBehavior */
        if ($request->getAcceptsJson()) {
            $creator = $draft->getCreator();
            return $this->asJson([
                'sourceId' => $draft->sourceId,
                'draftId' => $draft->draftId,
                'timestamp' => Craft::$app->getFormatter()->asTimestamp($draft->dateUpdated, 'short'),
                'creator' => $creator ? $creator->getName() : null,
                'draftName' => $draft->draftName,
                'draftNotes' => $draft->draftNotes,
                'docTitle' => $this->docTitle($draft),
                'title' => $this->pageTitle($draft),
                'duplicatedElements' => $elementsService::$duplicatedElementIds,
                'previewTargets' => $draft->getPreviewTargets(),
            ]);
        }

        Craft::$app->getSession()->setNotice(Craft::t('app', 'Draft saved.'));
        return $this->redirectToPostedUrl($draft);
    }

    /**
     * Deletes a draft.
     *
     * @return Response
     * @throws NotFoundHttpException if the requested entry draft cannot be found
     */
    public function actionDeleteDraft(): Response
    {
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();
        $draftId = $request->getBodyParam('draftId');

        /** @var ElementInterface|DraftBehavior $draft */
        $draft = Entry::find()
            ->draftId($draftId)
            ->siteId('*')
            ->anyStatus()
            ->one();

        if (!$draft) {
            throw new NotFoundHttpException('Draft not found');
        }

        if (!$draft->creatorId || $draft->creatorId != Craft::$app->getUser()->getIdentity()->id) {
            $this->requirePermission('deletePeerEntryDrafts:' . $draft->getSection()->uid);
        }

        Craft::$app->getElements()->deleteElement($draft, true);

        Craft::$app->getSession()->setNotice(Craft::t('app', 'Draft deleted'));

        if ($request->getAcceptsJson()) {
            return $this->asJson([
                'success' => true,
            ]);
        }

        return $this->redirectToPostedUrl();
    }

    /**
     * Publish a draft.
     *
     * @return Response|null
     * @throws NotFoundHttpException if the requested entry draft cannot be found
     * @throws ServerErrorHttpException if the entry draft is missing its entry
     * @throws ForbiddenHttpException if the user doesn't have the necessary permissions
     */
    public function actionPublishDraft()
    {
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();
        $draftId = $request->getRequiredBodyParam('draftId');
        $siteId = $request->getBodyParam('siteId');

        /** @var Entry|DraftBehavior|null $draft */
        $draft = Entry::find()
            ->draftId($draftId)
            ->siteId($siteId)
            ->anyStatus()
            ->one();

        if (!$draft) {
            throw new NotFoundHttpException('Draft not found');
        }

        // Permission enforcement
        /** @var Entry|null $entry */
        $entry = ElementHelper::sourceElement($draft);
        $this->enforceEditEntryPermissions($entry);
        $section = $entry->getSection();

        // Is this another user's entry (and it's not a Single)?
        $userId = Craft::$app->getUser()->getId();
        if (
            $entry->authorId != $userId &&
            $section->type != Section::TYPE_SINGLE &&
            $entry->enabled
        ) {
            // Make sure they have permission to make live changes to those
            $this->requirePermission('publishPeerEntries:' . $section->uid);
        }

        // Is this another user's draft?
        if ($draft->creatorId != $userId) {
            $this->requirePermission('publishPeerEntryDrafts:' . $section->uid);
        }

        // Populate the main draft attributes
        $this->_setDraftAttributesFromPost($draft);

        // Even more permission enforcement
        if ($draft->enabled && !Craft::$app->getUser()->checkPermission("publishEntries:{$section->uid}")) {
            if ($draft->getIsUnsavedDraft()) {
                // Just disable it
                $draft->enabled = false;
            } else {
                throw new ForbiddenHttpException('User is not permitted to perform this action');
            }
        }

        // Populate the field content
        $fieldsLocation = $request->getParam('fieldsLocation', 'fields');
        $draft->setFieldValuesFromRequest($fieldsLocation);
        $draft->updateTitle();

        // Validate and save the draft
        if ($draft->enabled && $draft->enabledForSite) {
            $draft->setScenario(Element::SCENARIO_LIVE);
        }

        if ($draft->getIsUnsavedDraft() && $request->getBodyParam('propagateAll')) {
            $draft->propagateAll = true;
        }

        try {
            if (!Craft::$app->getElements()->saveElement($draft)) {
                throw new InvalidElementException($draft);
            }

            // Publish the draft (finally!)
            $newEntry = Craft::$app->getDrafts()->applyDraft($draft);
        } catch (InvalidElementException $e) {
            Craft::$app->getSession()->setError(Craft::t('app', 'Couldn’t publish draft.'));

            // Send the draft back to the template
            Craft::$app->getUrlManager()->setRouteParams([
                'entry' => $draft
            ]);
            return null;
        }

        if ($request->getAcceptsJson()) {
            return $this->asJson([
                'success' => true,
            ]);
        }

        Craft::$app->getSession()->setNotice(Craft::t('app', 'Entry saved.'));
        return $this->redirectToPostedUrl($newEntry);
    }

    /**
     * Reverts an entry to a version.
     *
     * @return Response|null
     * @throws NotFoundHttpException if the requested entry version cannot be found
     * @throws ServerErrorHttpException if the entry version is missing its entry
     */
    public function actionRevertEntryToVersion()
    {
        $this->requirePostRequest();

        $revisionId = Craft::$app->getRequest()->getBodyParam('revisionId');
        $revision = Entry::find()
            ->revisionId($revisionId)
            ->siteId('*')
            ->unique()
            ->anyStatus()
            ->one();

        if (!$revision) {
            throw new NotFoundHttpException('Entry version not found');
        }

        // Permission enforcement
        /** @var Entry $entry */
        $entry = ElementHelper::sourceElement($revision);

        $this->enforceEditEntryPermissions($entry);
        $userId = Craft::$app->getUser()->getId();

        // Is this another user's entry (and it's not a Single)?
        if (
            $entry->authorId != $userId &&
            $entry->getSection()->type !== Section::TYPE_SINGLE &&
            $entry->enabled
        ) {
            // Make sure they have permission to make live changes to those
            $this->requirePermission('publishPeerEntries:' . $entry->getSection()->uid);
        }

        if ($entry->enabled) {
            $this->requirePermission('publishEntries:' . $entry->getSection()->uid);
        }

        // Revert to the version
        Craft::$app->getRevisions()->revertToRevision($revision, $userId);
        Craft::$app->getSession()->setNotice(Craft::t('app', 'Entry reverted to past revision.'));
        return $this->redirectToPostedUrl($revision);
    }

    /**
     * Sets a draft's attributes from the post data.
     *
     * @param Entry $draft
     */
    private function _setDraftAttributesFromPost(Entry $draft)
    {
        $request = Craft::$app->getRequest();
        /** @var Entry|DraftBehavior $draft */
        $draft->typeId = $request->getBodyParam('typeId');
        // Prevent the last entry type's field layout from being used
        $draft->fieldLayoutId = null;
        // Default to a temp slug to avoid slug validation errors
        $draft->slug = $request->getBodyParam('slug') ?: (ElementHelper::isTempSlug($draft->slug)
            ? $draft->slug
            : ElementHelper::tempSlug());
        if (($postDate = $request->getBodyParam('postDate')) !== null) {
            $draft->postDate = DateTimeHelper::toDateTime($postDate) ?: null;
        }
        if (($expiryDate = $request->getBodyParam('expiryDate')) !== null) {
            $draft->expiryDate = DateTimeHelper::toDateTime($expiryDate) ?: null;
        }

        $enabledForSite = $this->enabledForSiteValue();
        if (is_array($enabledForSite)) {
            // Set the global status to true if it's enabled for *any* sites, or if already enabled.
            $draft->enabled = in_array(true, $enabledForSite, false) || $draft->enabled;
        } else {
            $draft->enabled = (bool)$request->getBodyParam('enabled', $draft->enabled);
        }
        $draft->setEnabledForSite($enabledForSite ?? $draft->getEnabledForSite());
        $draft->title = $request->getBodyParam('title');

        if (!$draft->typeId) {
            // Default to the section's first entry type
            $draft->typeId = $draft->getSection()->getEntryTypes()[0]->id;
            // Prevent the last entry type's field layout from being used
            $draft->fieldLayoutId = null;
        }

        // Author
        $authorId = $request->getBodyParam('author', ($draft->authorId ?: Craft::$app->getUser()->getIdentity()->id));

        if (is_array($authorId)) {
            $authorId = $authorId[0] ?? null;
        }

        $draft->authorId = $authorId;

        // Parent
        $parentId = $request->getBodyParam('parentId');

        if (is_array($parentId)) {
            $parentId = $parentId[0] ?? null;
        }

        $draft->newParentId = $parentId ?: null;
    }
}
