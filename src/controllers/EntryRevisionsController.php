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
use craft\behaviors\RevisionBehavior;
use craft\elements\Entry;
use craft\helpers\DateTimeHelper;
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

        $request = Craft::$app->getRequest();
        $elementsService = Craft::$app->getElements();

        $draftId = $request->getBodyParam('draftId');
        $entryId = $request->getBodyParam('entryId');
        $siteId = $request->getBodyParam('siteId') ?: Craft::$app->getSites()->getPrimarySite()->id;
        $fieldsLocation = $request->getParam('fieldsLocation', 'fields');

        // Are we creating a new entry too?
        if (!$draftId && !$entryId) {
            $entry = new Entry();
            $entry->sectionId = $request->getBodyParam('entryId');
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
                /** @var Entry|DraftBehavior $draft */
                $draft = Craft::$app->getDrafts()->createDraft($entry, Craft::$app->getUser()->getId());
            }

            $this->_setDraftAttributesFromPost($draft);
            $draft->setFieldValuesFromRequest($fieldsLocation);
            $draft->updateTitle();

            if (!$elementsService->saveElement($draft)) {
                if ($request->getAcceptsJson()) {
                    return $this->asJson([
                        'errors' => $draft->getErrors(),
                    ]);
                }

                Craft::$app->getSession()->setError(Craft::t('app', 'Couldn’t save draft.'));
                Craft::$app->getUrlManager()->setRouteParams([
                    'entry' => $draft,
                ]);
                return null;
            }
        }

        // Make sure the user is authorized to preview the draft
        Craft::$app->getSession()->authorize('previewDraft:' . $draft->draftId);

        /** @var ElementInterface|DraftBehavior */
        if ($request->getAcceptsJson()) {
            return $this->asJson([
                'sourceId' => $draft->sourceId,
                'draftId' => $draft->draftId,
                'creator' => (string)$draft->getCreator(),
                'draftName' => $draft->draftName,
                'draftNotes' => $draft->draftNotes,
                'docTitle' => $this->docTitle($draft),
                'title' => $this->pageTitle($draft),
                'duplicatedElements' => $elementsService::$duplicatedElementIds,
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
        /** @var Entry $entry */
        $entry = $draft->getSource();
        $this->enforceEditEntryPermissions($entry);

        // Is this another user's entry (and it's not a Single)?
        $userId = Craft::$app->getUser()->getId();
        if (
            $entry->authorId != $userId &&
            $entry->getSection()->type != Section::TYPE_SINGLE &&
            $entry->enabled
        ) {
            // Make sure they have permission to make live changes to those
            $this->requirePermission('publishPeerEntries:' . $entry->getSection()->uid);
        }

        // Is this another user's draft?
        if ($draft->creatorId != $userId) {
            $this->requirePermission('publishPeerEntryDrafts:' . $entry->getSection()->uid);
        }

        // Populate the main draft attributes
        $this->_setDraftAttributesFromPost($draft);

        // Even more permission enforcement
        if ($draft->enabled) {
            $this->requirePermission('publishEntries:' . $entry->getSection()->uid);
        }

        // Populate the field content
        $fieldsLocation = $request->getParam('fieldsLocation', 'fields');
        $draft->setFieldValuesFromRequest($fieldsLocation);
        $draft->updateTitle();

        // Validate it
        if ($draft->enabled && $draft->enabledForSite) {
            $entry->setScenario(Element::SCENARIO_LIVE);
        }

        if (!$draft->validate()) {
            Craft::$app->getSession()->setError(Craft::t('app', 'Couldn’t publish draft.'));

            // Send the draft back to the template
            Craft::$app->getUrlManager()->setRouteParams([
                'entry' => $draft
            ]);
        }

        // Publish the draft (finally!)
        $newEntry = Craft::$app->getDrafts()->applyDraft($draft);
        Craft::$app->getSession()->setNotice(Craft::t('app', 'Entry updated.'));

        if ($request->getAcceptsJson()) {
            return $this->asJson([
                'success' => true,
            ]);
        }

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
        /** @var Entry|RevisionBehavior $revision */
        $entry = $revision->getSource();

        if (!$entry) {
            throw new ServerErrorHttpException('Entry version is missing its entry');
        }

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

    // Private Methods
    // =========================================================================

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
        $draft->slug = $request->getBodyParam('slug');
        if (($postDate = $request->getBodyParam('postDate')) !== null) {
            $draft->postDate = DateTimeHelper::toDateTime($postDate) ?: null;
        }
        if (($expiryDate = $request->getBodyParam('expiryDate')) !== null) {
            $draft->expiryDate = DateTimeHelper::toDateTime($expiryDate) ?: null;
        }
        $draft->enabled = (bool)$request->getBodyParam('enabled');
        $draft->title = $request->getBodyParam('title');

        if (!$draft->typeId) {
            // Default to the section's first entry type
            $draft->typeId = $draft->getSection()->getEntryTypes()[0]->id;
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
