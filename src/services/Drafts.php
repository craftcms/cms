<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use Craft;
use craft\base\Element;
use craft\base\ElementInterface;
use craft\behaviors\DraftBehavior;
use craft\behaviors\RevisionBehavior;
use craft\db\Table;
use craft\errors\InvalidElementException;
use craft\events\DraftEvent;
use yii\base\Component;
use yii\base\InvalidArgumentException;
use yii\db\Exception as DbException;

/**
 * Drafts service.
 * An instance of the Drafts service is globally accessible in Craft via [[\craft\base\ApplicationTrait::getDrafts()|`Craft::$app->drafts`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.2
 */
class Drafts extends Component
{
    // Constants
    // =========================================================================

    /**
     * @event DraftEvent The event that is triggered before a draft is created.
     */
    const EVENT_BEFORE_CREATE_DRAFT = 'beforeCreateDraft';

    /**
     * @event DraftEvent The event that is triggered after a draft is created.
     */
    const EVENT_AFTER_CREATE_DRAFT = 'afterCreateDraft';

    /**
     * @event DraftEvent The event that is triggered before a draft is published.
     */
    const EVENT_BEFORE_PUBLISH_DRAFT = 'beforePublishDraft';

    /**
     * @event DraftEvent The event that is triggered after a draft is published.
     */
    const EVENT_AFTER_PUBLISH_DRAFT = 'afterPublishDraft';

    // Public Methods
    // =========================================================================

    /**
     * Returns drafts for a given element ID that the current user is allowed to edit
     *
     * @param ElementInterface $element
     * @param string|null $permission
     * @return ElementInterface[]
     */
    public function getEditableDrafts(ElementInterface $element, string $permission = null): array
    {
        $user = Craft::$app->getUser()->getIdentity();
        if (!$user) {
            return [];
        }

        /** @var Element $element */
        $query = $element::find()
            ->draftOf($element)
            ->siteId($element->siteId)
            ->anyStatus()
            ->orderBy(['dateUpdated' => SORT_DESC]);

        if (!$permission || !$user->can($permission)) {
            $query->draftCreator($user);
        }

        return $query->all();
    }

    /**
     * Creates a new draft for the given element.
     *
     * @param ElementInterface $source The element to create a revision for
     * @param int $creatorId The user ID that the draft should be attributed to
     * @param string|null $name The draft name
     * @param string|null $notes The draft notes
     * @return ElementInterface The new draft
     * @throws \Throwable
     */
    public function createDraft(ElementInterface $source, int $creatorId, string $name = null, string $notes = null): ElementInterface
    {
        // Make sure the source isn't a draft or revision
        /** @var Element $source */
        if ($source->draftId || $source->revisionId) {
            throw new InvalidArgumentException('Cannot create a draft from another draft or revision.');
        }

        // Fire a 'beforeCreateDraft' event
        $event = new DraftEvent([
            'source' => $source,
            'creatorId' => $creatorId,
            'draftName' => $name,
            'draftNotes' => $notes,
        ]);
        $this->trigger(self::EVENT_BEFORE_CREATE_DRAFT, $event);
        $name = $event->draftName;
        $notes = $event->draftNotes;

        if ($name === null || $name === '') {
            $totalDrafts = $source::find()
                ->draftOf($source)
                ->siteId($source->siteId)
                ->anyStatus()
                ->count();
            $name = Craft::t('app', 'Draft {num}', ['num' => $totalDrafts + 1]);
        }

        $transaction = Craft::$app->getDb()->beginTransaction();
        try {
            // Create a new revision for the source element, or get the latest if nothing has changed since then
            /** @var Element|RevisionBehavior $revision */
            $revision = Craft::$app->getRevisions()->createRevision($source, $creatorId, 'Created automatically for draft');

            // Create the draft row
            $db = Craft::$app->getDb();
            $db->createCommand()
                ->insert(Table::DRAFTS, [
                    'sourceId' => $source->id,
                    'revisionId' => $revision->revisionId,
                    'creatorId' => $creatorId,
                    'name' => $name,
                    'notes' => $notes,
                ], false)
                ->execute();
            $draftId = $db->getLastInsertID(Table::DRAFTS);

            // Duplicate the element
            /** @var Element $draft */
            $draft = Craft::$app->getElements()->duplicateElement($source, [
                'draftId' => $draftId,
            ]);

            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        $draft->attachBehavior('draft', new DraftBehavior([
            'sourceId' => $source->id,
            'creatorId' => $creatorId,
            'draftName' => $name,
            'draftNotes' => $notes,
        ]));

        // Fire an 'afterCreateDraft' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_CREATE_DRAFT)) {
            $this->trigger(self::EVENT_AFTER_CREATE_DRAFT, new DraftEvent([
                'source' => $source,
                'creatorId' => $creatorId,
                'draftName' => $name,
                'draftNotes' => $notes,
                'draft' => $draft,
            ]));
        }

        return $draft;
    }

    /**
     * Updates the name and notes of a draft.
     *
     * @param int $draftId
     * @param string $name
     * @param string|null $notes
     * @throws DbException
     */
    public function updateDraftName(int $draftId, string $name, string $notes = null)
    {
        Craft::$app->getDb()->createCommand()
            ->update(Table::DRAFTS, [
                'name' => $name,
                'notes' => $notes
            ], ['id' => $draftId], [], false)
            ->execute();
    }

    /**
     * Publishes a draft.
     *
     * @param ElementInterface $draft
     * @return ElementInterface The new source element
     * @throws \Throwable
     */
    public function publishDraft(ElementInterface $draft): ElementInterface
    {
        /** @var Element|DraftBehavior $draft */
        /** @var Element $source */
        $source = $draft->getSource();

        // Fire a 'beforePublishDraft' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_PUBLISH_DRAFT)) {
            $this->trigger(self::EVENT_BEFORE_PUBLISH_DRAFT, new DraftEvent([
                'source' => $source,
                'creatorId' => $draft->creatorId,
                'draftName' => $draft->draftName,
                'draftNotes' => $draft->draftNotes,
                'draft' => $draft,
            ]));
        }

        $transaction = Craft::$app->getDb()->beginTransaction();
        try {
            // "Duplicate" the draft with the source element's ID, UID, and content ID
            $elementsService = Craft::$app->getElements();
            $newSource = $elementsService->duplicateElement($draft, [
                'id' => $source->id,
                'uid' => $source->uid,
                'draftId' => null,
                'revisionNotes' => $draft->draftNotes,
            ]);

            // Now delete the draft
            $elementsService->deleteElement($draft, true);

            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        // Fire an 'afterPublishDraft' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_PUBLISH_DRAFT)) {
            $this->trigger(self::EVENT_AFTER_PUBLISH_DRAFT, new DraftEvent([
                'source' => $newSource,
                'creatorId' => $draft->creatorId,
                'draftName' => $draft->draftName,
                'draftNotes' => $draft->draftNotes,
                'draft' => $draft,
            ]));
        }

        return $newSource;
    }
}
