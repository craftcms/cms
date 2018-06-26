<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use Craft;
use craft\helpers\DateTimeHelper;
use craft\models\EntryDraft;
use craft\models\Section;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\web\ServerErrorHttpException;

/**
 * The EntryRevisionsController class is a controller that handles various entry version and draft related tasks such as
 * retrieving, saving, deleting, publishing and reverting entry drafts and versions.
 * Note that all actions in the controller require an authenticated Craft session via [[allowAnonymous]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class EntryRevisionsController extends BaseEntriesController
{
    // Public Methods
    // =========================================================================

    /**
     * Saves a draft, or creates a new one.
     *
     * @return Response|null
     * @throws NotFoundHttpException if the requested entry draft cannot be found
     */
    public function actionSaveDraft()
    {
        $this->requirePostRequest();

        $draftId = Craft::$app->getRequest()->getBodyParam('draftId');

        if ($draftId) {
            $draft = Craft::$app->getEntryRevisions()->getDraftById($draftId);

            if (!$draft) {
                throw new NotFoundHttpException('Entry draft not found');
            }
        } else {
            $draft = new EntryDraft([
                'id' => Craft::$app->getRequest()->getBodyParam('entryId'),
                'sectionId' => Craft::$app->getRequest()->getRequiredBodyParam('sectionId'),
                'creatorId' => Craft::$app->getUser()->getIdentity()->id,
                'siteId' => Craft::$app->getRequest()->getBodyParam('siteId') ?: Craft::$app->getSites()->getPrimarySite()->id,
            ]);
        }

        // Make sure they have permission to be editing this
        $this->enforceEditEntryPermissions($draft);

        $this->_setDraftAttributesFromPost($draft);

        $fieldsLocation = Craft::$app->getRequest()->getParam('fieldsLocation', 'fields');
        $draft->setFieldValuesFromRequest($fieldsLocation);
        $draft->updateTitle();

        // Manually validate 'title' since the Elements service will just give it a title automatically.
        if (!$draft->id && $draft->validate(['title'])) {
            // Don't save brand new entries as enabled
            $enabled = $draft->enabled;
            $draft->enabled = false;
            Craft::$app->getElements()->saveElement($draft, false);
            $draft->enabled = $enabled;
        }

        if (!$draft->id || !Craft::$app->getEntryRevisions()->saveDraft($draft)) {
            Craft::$app->getSession()->setError(Craft::t('app', 'Couldn’t save draft.'));

            // Send the draft back to the template
            Craft::$app->getUrlManager()->setRouteParams([
                'entry' => $draft
            ]);

            return null;
        }

        Craft::$app->getSession()->setNotice(Craft::t('app', 'Draft saved.'));

        return $this->redirectToPostedUrl($draft);
    }

    /**
     * Renames a draft.
     *
     * @return Response
     * @throws NotFoundHttpException if the requested entry draft cannot be found
     */
    public function actionUpdateDraftMeta(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $draftId = Craft::$app->getRequest()->getRequiredBodyParam('draftId');
        $name = Craft::$app->getRequest()->getRequiredBodyParam('name');

        $draft = Craft::$app->getEntryRevisions()->getDraftById($draftId);

        if (!$draft) {
            throw new NotFoundHttpException('Entry draft not found');
        }

        if (!$draft->creatorId || $draft->creatorId != Craft::$app->getUser()->getIdentity()->id) {
            // Make sure they have permission to be doing this
            $this->requirePermission('editPeerEntryDrafts:' . $draft->sectionId);
        }

        $draft->name = $name;
        $draft->revisionNotes = Craft::$app->getRequest()->getBodyParam('notes');

        if (Craft::$app->getEntryRevisions()->saveDraft($draft)) {
            return $this->asJson(['success' => true]);
        }

        return $this->asErrorJson($draft->getFirstError('name'));
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

        $draftId = Craft::$app->getRequest()->getBodyParam('draftId');
        $draft = Craft::$app->getEntryRevisions()->getDraftById($draftId);

        if (!$draft) {
            throw new NotFoundHttpException('Entry draft not found');
        }

        if (!$draft->creatorId || $draft->creatorId != Craft::$app->getUser()->getIdentity()->id) {
            $this->requirePermission('deletePeerEntryDrafts:' . $draft->sectionId);
        }

        Craft::$app->getEntryRevisions()->deleteDraft($draft);

        return $this->redirectToPostedUrl();
    }

    /**
     * Publish a draft.
     *
     * @return Response|null
     * @throws NotFoundHttpException if the requested entry draft cannot be found
     * @throws ServerErrorHttpException if the entry draft is missing its entry
     */
    public function actionPublishDraft()
    {
        $this->requirePostRequest();

        $draftId = Craft::$app->getRequest()->getBodyParam('draftId');
        $draft = Craft::$app->getEntryRevisions()->getDraftById($draftId);
        $userId = Craft::$app->getUser()->getIdentity()->id;

        if (!$draft) {
            throw new NotFoundHttpException('Entry draft not found');
        }

        // Permission enforcement
        $entry = Craft::$app->getEntries()->getEntryById($draft->id, $draft->siteId);

        if (!$entry) {
            throw new ServerErrorHttpException('Entry draft is missing its entry');
        }

        $this->enforceEditEntryPermissions($entry);
        $userSessionService = Craft::$app->getUser();

        // Is this another user's entry (and it's not a Single)?
        if (
            $entry->authorId != $userSessionService->getIdentity()->id &&
            $entry->getSection()->type != Section::TYPE_SINGLE &&
            $entry->enabled
        ) {
            // Make sure they have permission to make live changes to those
            $this->requirePermission('publishPeerEntries:' . $entry->sectionId);
        }

        // Is this another user's draft?
        if (!$draft->creatorId || $draft->creatorId != $userId) {
            $this->requirePermission('publishPeerEntryDrafts:' . $entry->sectionId);
        }

        // Populate the main draft attributes
        $this->_setDraftAttributesFromPost($draft);

        // Even more permission enforcement
        if ($draft->enabled) {
            $this->requirePermission('publishEntries:' . $entry->sectionId);
        }

        // Populate the field content
        $fieldsLocation = Craft::$app->getRequest()->getParam('fieldsLocation', 'fields');
        $draft->setFieldValuesFromRequest($fieldsLocation);
        $draft->updateTitle();

        // Publish the draft (finally!)
        if (!Craft::$app->getEntryRevisions()->publishDraft($draft)) {
            Craft::$app->getSession()->setError(Craft::t('app', 'Couldn’t publish draft.'));

            // Send the draft back to the template
            Craft::$app->getUrlManager()->setRouteParams([
                'entry' => $draft
            ]);

            return null;
        }

        Craft::$app->getSession()->setNotice(Craft::t('app', 'Draft published.'));

        return $this->redirectToPostedUrl($draft);
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

        $versionId = Craft::$app->getRequest()->getBodyParam('versionId');
        $version = Craft::$app->getEntryRevisions()->getVersionById($versionId);

        if (!$version) {
            throw new NotFoundHttpException('Entry version not found');
        }

        // Permission enforcement
        $entry = Craft::$app->getEntries()->getEntryById($version->id, $version->siteId);

        if (!$entry) {
            throw new ServerErrorHttpException('Entry version is missing its entry');
        }

        $this->enforceEditEntryPermissions($entry);
        $userSessionService = Craft::$app->getUser();

        // Is this another user's entry (and it's not a Single)?
        if (
            $entry->authorId != $userSessionService->getIdentity()->id &&
            $entry->getSection()->type !== Section::TYPE_SINGLE &&
            $entry->enabled
        ) {
            // Make sure they have permission to make live changes to those
            $this->requirePermission('publishPeerEntries:' . $entry->sectionId);
        }

        if ($entry->enabled) {
            $this->requirePermission('publishEntries:' . $entry->sectionId);
        }

        // Revert to the version
        if (!Craft::$app->getEntryRevisions()->revertEntryToVersion($version)) {
            Craft::$app->getSession()->setError(Craft::t('app', 'Couldn’t revert entry to past version.'));

            // Send the version back to the template
            Craft::$app->getUrlManager()->setRouteParams([
                'entry' => $version
            ]);

            return null;
        }

        Craft::$app->getSession()->setNotice(Craft::t('app', 'Entry reverted to past version.'));

        return $this->redirectToPostedUrl($version);
    }

    // Private Methods
    // =========================================================================

    /**
     * Sets a draft's attributes from the post data.
     *
     * @param EntryDraft $draft
     */
    private function _setDraftAttributesFromPost(EntryDraft $draft)
    {
        $draft->typeId = Craft::$app->getRequest()->getBodyParam('typeId');
        $draft->slug = Craft::$app->getRequest()->getBodyParam('slug');
        if (($postDate = Craft::$app->getRequest()->getBodyParam('postDate')) !== null) {
            $draft->postDate = DateTimeHelper::toDateTime($postDate) ?: null;
        }
        if (($expiryDate = Craft::$app->getRequest()->getBodyParam('expiryDate')) !== null) {
            $draft->expiryDate = DateTimeHelper::toDateTime($expiryDate) ?: null;
        }
        $draft->enabled = (bool)Craft::$app->getRequest()->getBodyParam('enabled');
        $draft->title = Craft::$app->getRequest()->getBodyParam('title');

        if (!$draft->typeId) {
            // Default to the section's first entry type
            $draft->typeId = $draft->getSection()->getEntryTypes()[0]->id;
        }

        // Author
        $authorId = Craft::$app->getRequest()->getBodyParam('author', ($draft->authorId ?: Craft::$app->getUser()->getIdentity()->id));

        if (is_array($authorId)) {
            $authorId = $authorId[0] ?? null;
        }

        $draft->authorId = $authorId;

        // Parent
        $parentId = Craft::$app->getRequest()->getBodyParam('parentId');

        if (is_array($parentId)) {
            $parentId = $parentId[0] ?? null;
        }

        $draft->newParentId = $parentId ?: null;
    }
}
