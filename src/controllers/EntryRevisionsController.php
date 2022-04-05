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
use craft\db\Query;
use craft\db\Table;
use craft\elements\Entry;
use craft\errors\InvalidElementException;
use craft\helpers\ArrayHelper;
use craft\helpers\Cp;
use craft\helpers\DateTimeHelper;
use craft\helpers\ElementHelper;
use craft\helpers\UrlHelper;
use craft\models\Section;
use craft\models\Section_SiteSettings;
use craft\services\Elements;
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
     * @throws ForbiddenHttpException
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
        $entry = new Entry();
        $entry->siteId = $site->id;
        $entry->sectionId = $section->id;
        $entry->authorId = $this->request->getQueryParam('authorId', Craft::$app->getUser()->getId());

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
            // Get the initially selected parent
            $entry->newParentId = $this->request->getParam('parentId');
            if (is_array($entry->newParentId)) {
                $entry->newParentId = reset($entry->newParentId) ?: null;
            }
        }

        // Make sure the user is allowed to create this entry
        $this->enforceSitePermission($entry->getSite());
        $this->enforceEditEntryPermissions($entry);

        // Title & slug
        $entry->title = $this->request->getQueryParam('title');
        $entry->slug = $this->request->getQueryParam('slug');
        if ($entry->title && !$entry->slug) {
            $entry->slug = ElementHelper::generateSlug($entry->title, null, $site->language);
        }
        if (!$entry->slug) {
            $entry->slug = ElementHelper::tempSlug();
        }

        // Post & expiry dates
        if (($postDate = $this->request->getQueryParam('postDate')) !== null) {
            $entry->postDate = DateTimeHelper::toDateTime($postDate);
        }
        if (($expiryDate = $this->request->getQueryParam('expiryDate')) !== null) {
            $entry->expiryDate = DateTimeHelper::toDateTime($expiryDate);
        }

        // Custom fields
        foreach ($entry->getFieldLayout()->getFields() as $field) {
            if (($value = $this->request->getQueryParam($field->handle)) !== null) {
                $entry->setFieldValue($field->handle, $value);
            }
        }

        // Save it
        $entry->setScenario(Element::SCENARIO_ESSENTIALS);
        if (!Craft::$app->getDrafts()->saveElementAsDraft($entry, Craft::$app->getUser()->getId(), null, null, false)) {
            throw new Exception('Unable to save entry as a draft: ' . implode(', ', $entry->getErrorSummary(true)));
        }

        // Set its position in the structure if a before/after parma was passed
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

        // Redirect to its edit page
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
     * @throws ForbiddenHttpException
     */
    public function actionSaveDraft()
    {
        $this->requirePostRequest();

        $elementsService = Craft::$app->getElements();

        $draftId = $this->request->getBodyParam('draftId');
        $entryId = $this->request->getBodyParam('sourceId') ?? $this->request->getBodyParam('entryId');
        $siteId = $this->request->getBodyParam('siteId') ?: Craft::$app->getSites()->getPrimarySite()->id;
        $fieldsLocation = $this->request->getParam('fieldsLocation', 'fields');
        $provisional = (bool)($this->request->getBodyParam('provisional') ?? false);
        $dropProvisional = (bool)($this->request->getBodyParam('dropProvisional') ?? false);

        // Are we creating a new entry too?
        if (!$draftId && !$entryId) {
            $entry = new Entry();
            $entry->siteId = $siteId;
            $entry->sectionId = $this->request->getBodyParam('sectionId');
            $this->_setDraftAttributesFromPost($entry);
            $this->enforceSitePermission($entry->getSite());
            $this->enforceEditEntryPermissions($entry);
            $entry->setFieldValuesFromRequest($fieldsLocation);
            $entry->updateTitle();

            $enabled = $entry->enabled;
            $entry->enabled = false;

            // Manually validate 'title' since the Elements service will just give it a title automatically.
            if (!$entry->validate(['title']) || !$elementsService->saveElement($entry, false)) {
                $this->setFailFlash(Craft::t('app', 'Couldn’t save draft.'));
                Craft::$app->getUrlManager()->setRouteParams([
                    'entry' => $entry,
                ]);
                return null;
            }

            $entry->enabled = $enabled;
            /** @var Entry|DraftBehavior $draft */
            $draft = Craft::$app->getDrafts()->createDraft($entry, Craft::$app->getUser()->getId(), null, null, [], $provisional);
        } else {
            $transaction = null;

            if ($draftId) {
                $draft = Entry::find()
                    ->draftId($draftId)
                    ->provisionalDrafts($provisional)
                    ->siteId($siteId)
                    ->anyStatus()
                    ->one();
                if (!$draft) {
                    throw new NotFoundHttpException('Entry draft not found');
                }
                $this->enforceSitePermission($draft->getSite());
                $this->enforceEditEntryPermissions($draft);
            } else {
                $entry = Entry::find()
                    ->id($entryId)
                    ->siteId($siteId)
                    ->anyStatus()
                    ->one();
                if (!$entry) {
                    throw new NotFoundHttpException('Entry not found');
                }
                $this->enforceSitePermission($entry->getSite());
                $this->enforceEditEntryPermissions($entry);

                if ($provisional) {
                    // Make sure a provisional draft doesn't already exist for this entry/user combo
                    $userId = Craft::$app->getUser()->getId();
                    $provisionalExists = Entry::find()
                        ->provisionalDrafts()
                        ->draftOf($entryId)
                        ->draftCreator($userId)
                        ->site('*')
                        ->anyStatus()
                        ->exists();

                    if ($provisionalExists) {
                        throw new BadRequestHttpException("A provisional draft already exists for entry/user $entryId/$userId.");
                    }
                }

                // Create the draft in a transaction so we can undo it if something goes wrong
                $transaction = Craft::$app->getDb()->beginTransaction();

                /** @var Entry|DraftBehavior $draft */
                $draft = Craft::$app->getDrafts()->createDraft($entry, Craft::$app->getUser()->getId(), null, null, [], $provisional);
            }

            $this->_setDraftAttributesFromPost($draft);
            $draft->setFieldValuesFromRequest($fieldsLocation);
            $draft->updateTitle();

            if ($dropProvisional) {
                $draft->isProvisionalDraft = false;
            }

            $draft->setScenario(Element::SCENARIO_ESSENTIALS);

            // todo: stop checking for propagateAll in Craft 4
            if (
                $draft->getIsUnpublishedDraft() &&
                (
                    $this->request->getBodyParam('isFresh') ||
                    $this->request->getBodyParam('propagateAll')
                )
            ) {
                $draft->setIsFresh();
                $draft->propagateAll = true;
            }

            if (!$elementsService->saveElement($draft)) {
                if ($transaction !== null) {
                    $transaction->rollBack();
                }

                if ($this->request->getAcceptsJson()) {
                    return $this->asJson([
                        'errors' => $draft->getErrorSummary(true),
                    ]);
                }

                $this->setFailFlash(Craft::t('app', 'Couldn’t save draft.'));
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
        if ($this->request->getAcceptsJson()) {
            $creator = $draft->getCreator();
            [$docTitle, $title] = Cp::editElementTitles($draft);
            return $this->asJson([
                'sourceId' => $draft->getCanonicalId(),
                'draftId' => $draft->draftId,
                'timestamp' => Craft::$app->getFormatter()->asTimestamp($draft->dateUpdated, 'short', true),
                'creator' => $creator ? $creator->getName() : null,
                'draftName' => $draft->draftName,
                'draftNotes' => $draft->draftNotes,
                'docTitle' => $docTitle,
                'title' => $title,
                'duplicatedElements' => Elements::$duplicatedElementIds,
                'previewTargets' => $draft->getPreviewTargets(),
                'modifiedAttributes' => $draft->getModifiedAttributes(),
            ]);
        }

        $this->setSuccessFlash(Craft::t('app', 'Draft saved.'));
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

        $draftId = $this->request->getBodyParam('draftId');
        $siteId = $this->request->getBodyParam('siteId');
        $provisional = (bool)($this->request->getBodyParam('provisional') ?? false);

        /** @var Entry|DraftBehavior $draft */
        $draft = Entry::find()
            ->draftId($draftId)
            ->provisionalDrafts($provisional)
            ->site('*')
            ->preferSites([$siteId])
            ->unique()
            ->anyStatus()
            ->one();

        if (!$draft) {
            throw new NotFoundHttpException('Draft not found');
        }

        $this->enforceDeleteEntryPermissions($draft);

        Craft::$app->getElements()->deleteElement($draft, true);

        if ($provisional) {
            $this->setSuccessFlash(Craft::t('app', 'Changes discarded'));
        } else {
            $this->setSuccessFlash(Craft::t('app', 'Draft deleted'));
        }

        if ($this->request->getAcceptsJson()) {
            return $this->asJson([
                'success' => true,
            ]);
        }

        return $this->redirectToPostedUrl($draft);
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

        $draftId = $this->request->getRequiredBodyParam('draftId');
        $siteId = $this->request->getBodyParam('siteId');
        $provisional = (bool)($this->request->getBodyParam('provisional') ?? false);

        // Get the structure ID
        $structureId = (new Query())
            ->select(['sections.structureId'])
            ->from(['sections' => Table::SECTIONS])
            ->innerJoin(['entries' => Table::ENTRIES], '[[entries.sectionId]] = [[sections.id]]')
            ->innerJoin(['elements' => Table::ELEMENTS], '[[elements.id]] = [[entries.id]]')
            ->where(['elements.draftId' => $draftId])
            ->scalar();

        /** @var Entry|DraftBehavior|null $draft */
        $draft = Entry::find()
            ->draftId($draftId)
            ->provisionalDrafts($provisional)
            ->siteId($siteId)
            ->structureId($structureId)
            ->anyStatus()
            ->one();

        if (!$draft) {
            throw new NotFoundHttpException('Draft not found');
        }

        // Permission enforcement
        /** @var Entry|null $entry */
        $this->enforceSitePermission($draft->getSite());
        $entry = $draft->getCanonical(true);
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
            if ($draft->getIsUnpublishedDraft()) {
                // Just disable it
                $draft->enabled = false;
            } else {
                throw new ForbiddenHttpException('User is not permitted to perform this action');
            }
        }

        // Populate the field content
        $fieldsLocation = $this->request->getParam('fieldsLocation', 'fields');
        $draft->setFieldValuesFromRequest($fieldsLocation);
        $draft->updateTitle();

        // Validate and save the draft
        if ($draft->enabled && $draft->getEnabledForSite()) {
            $draft->setScenario(Element::SCENARIO_LIVE);
        }

        // todo: stop checking for propagateAll in Craft 4
        if (
            $draft->getIsUnpublishedDraft() &&
            (
                $this->request->getBodyParam('isFresh') ||
                $this->request->getBodyParam('propagateAll')
            )
        ) {
            $draft->setIsFresh();
            $draft->propagateAll = true;
        }

        try {
            if (!Craft::$app->getElements()->saveElement($draft)) {
                throw new InvalidElementException($draft);
            }

            // Apply the draft (finally!)
            $isDerivative = $draft->getIsDerivative();
            if ($isDerivative) {
                $lockKey = "entry:$draft->canonicalId";
                $mutex = Craft::$app->getMutex();
                if (!$mutex->acquire($lockKey, 15)) {
                    throw new Exception('Could not acquire a lock to save the entry.');
                }
            }

            try {
                $newEntry = Craft::$app->getDrafts()->applyDraft($draft);
            } finally {
                if ($isDerivative) {
                    $mutex->release($lockKey);
                }
            }
        } catch (InvalidElementException $e) {
            if ($draft->getIsUnpublishedDraft()) {
                $this->setFailFlash(Craft::t('app', 'Couldn’t create entry.'));
            } elseif ($draft->isProvisionalDraft) {
                $this->setFailFlash(Craft::t('app', 'Couldn’t save entry.'));
            } else {
                $this->setFailFlash(Craft::t('app', 'Couldn’t apply draft.'));
            }

            // Send the draft back to the template
            Craft::$app->getUrlManager()->setRouteParams([
                'entry' => $draft,
            ]);
            return null;
        }

        if ($this->request->getAcceptsJson()) {
            return $this->asJson([
                'success' => true,
            ]);
        }

        if ($draft->getIsUnpublishedDraft()) {
            $this->setSuccessFlash(Craft::t('app', 'Entry created.'));
        } elseif ($draft->isProvisionalDraft) {
            $this->setSuccessFlash(Craft::t('app', 'Entry saved.'));
        } else {
            $this->setSuccessFlash(Craft::t('app', 'Draft applied.'));
        }

        // Let any other browser windows editing the draft that they can reload themselves
        if ($draft->draftId) {
            $js = <<<JS
if (typeof BroadcastChannel !== 'undefined') {
    (new BroadcastChannel('DraftEditor')).postMessage({
        event: 'saveDraft',
        canonicalId: $draft->canonicalId,
        draftId: $draft->draftId,
        isProvisionalDraft: false,
    });
}
JS;
            Craft::$app->getSession()->addJsFlash($js);
        }

        return $this->redirectToPostedUrl($newEntry);
    }

    /**
     * Reverts an entry to a version.
     *
     * @return Response|null
     * @throws NotFoundHttpException if the requested entry version cannot be found
     * @throws ServerErrorHttpException if the entry version is missing its entry
     * @throws ForbiddenHttpException
     */
    public function actionRevertEntryToVersion()
    {
        $this->requirePostRequest();

        $revisionId = $this->request->getBodyParam('revisionId');
        $revision = Entry::find()
            ->revisionId($revisionId)
            ->site('*')
            ->unique()
            ->anyStatus()
            ->one();

        if (!$revision) {
            throw new NotFoundHttpException('Entry version not found');
        }

        // Permission enforcement
        $entry = $revision->getCanonical();

        $this->enforceSitePermission($entry->getSite());
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
        $this->setSuccessFlash(Craft::t('app', 'Entry reverted to past revision.'));
        return $this->redirectToPostedUrl($entry);
    }

    /**
     * Sets a draft's attributes from the post data.
     *
     * @param Entry $draft
     */
    private function _setDraftAttributesFromPost(Entry $draft)
    {
        /** @var Entry|DraftBehavior $draft */
        $draft->typeId = $this->request->getBodyParam('typeId');
        // Prevent the last entry type's field layout from being used
        $draft->fieldLayoutId = null;
        // Default to a temp slug to avoid slug validation errors
        $draft->slug = $this->request->getBodyParam('slug') ?: ($draft->slug !== null && ElementHelper::isTempSlug($draft->slug)
            ? $draft->slug
            : ElementHelper::tempSlug());
        if (($postDate = $this->request->getBodyParam('postDate')) !== null) {
            $draft->postDate = DateTimeHelper::toDateTime($postDate) ?: null;
        }
        if (($expiryDate = $this->request->getBodyParam('expiryDate')) !== null) {
            $draft->expiryDate = DateTimeHelper::toDateTime($expiryDate) ?: null;
        }

        $enabledForSite = $this->enabledForSiteValue();
        if (is_array($enabledForSite)) {
            // Set the global status to true if it's enabled for *any* sites, or if already enabled.
            $draft->enabled = in_array(true, $enabledForSite, false) || $draft->enabled;
        } else {
            $draft->enabled = (bool)$this->request->getBodyParam('enabled', $draft->enabled);
        }
        $draft->setEnabledForSite($enabledForSite ?? $draft->getEnabledForSite());
        $draft->title = $this->request->getBodyParam('title') ?? $draft->title;

        if (!$draft->typeId) {
            // Default to the section's first entry type
            $draft->typeId = $draft->getAvailableEntryTypes()[0]->id;
            // Prevent the last entry type's field layout from being used
            $draft->fieldLayoutId = null;
        }

        // Author
        $authorId = $this->request->getBodyParam('author', ($draft->authorId ?: Craft::$app->getUser()->getIdentity()->id));

        if (is_array($authorId)) {
            $authorId = $authorId[0] ?? null;
        }

        $draft->authorId = $authorId;

        // Parent
        if (($parentId = $this->request->getBodyParam('parentId')) !== null) {
            if (is_array($parentId)) {
                $parentId = reset($parentId) ?: false;
            }
            $draft->newParentId = $parentId ?: false;
        }

        // Draft meta
        /** @var DraftBehavior|null $behavior */
        $behavior = $draft->getBehavior('draft');
        if ($behavior) {
            $behavior->draftName = $this->request->getBodyParam('draftName') ?? $behavior->draftName;
            $behavior->draftNotes = $this->request->getBodyParam('notes') ?? $behavior->draftNotes;
        }
    }
}
