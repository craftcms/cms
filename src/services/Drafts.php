<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use Craft;
use craft\base\ElementInterface;
use craft\behaviors\DraftBehavior;
use craft\db\Connection;
use craft\db\Query;
use craft\db\Table;
use craft\errors\InvalidElementException;
use craft\events\DraftEvent;
use craft\helpers\ArrayHelper;
use craft\helpers\DateTimeHelper;
use craft\helpers\Db;
use craft\helpers\ElementHelper;
use yii\base\Component;
use yii\base\Exception;
use yii\base\InvalidArgumentException;
use yii\db\Exception as DbException;
use yii\di\Instance;

/**
 * Drafts service.
 * An instance of the Drafts service is globally accessible in Craft via [[\craft\base\ApplicationTrait::getDrafts()|`Craft::$app->drafts`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.2.0
 */
class Drafts extends Component
{
    /**
     * @event DraftEvent The event that is triggered before a draft is created.
     */
    const EVENT_BEFORE_CREATE_DRAFT = 'beforeCreateDraft';

    /**
     * @event DraftEvent The event that is triggered after a draft is created.
     */
    const EVENT_AFTER_CREATE_DRAFT = 'afterCreateDraft';

    /**
     * @event DraftEvent The event that is triggered before source changes are merged into a draft.
     * @since 3.4.0
     */
    const EVENT_BEFORE_MERGE_SOURCE_CHANGES = 'beforeMergeSource';

    /**
     * @event DraftEvent The event that is triggered after source changes are merged into a draft.
     * @since 3.4.0
     */
    const EVENT_AFTER_MERGE_SOURCE_CHANGES = 'afterMergeSource';

    /**
     * @event DraftEvent The event that is triggered before a draft is published.
     * @since 3.6.0
     */
    const EVENT_BEFORE_PUBLISH_DRAFT = 'beforePublishDraft';

    /**
     * @event DraftEvent The event that is triggered after a draft is published.
     * @since 3.6.0
     */
    const EVENT_AFTER_PUBLISH_DRAFT = 'afterPublishDraft';

    /**
     * @event DraftEvent The event that is triggered before a draft is published.
     * @deprecated in 3.6.0. Use [[EVENT_BEFORE_PUBLISH_DRAFT]] instead.
     */
    const EVENT_BEFORE_APPLY_DRAFT = self::EVENT_BEFORE_PUBLISH_DRAFT;

    /**
     * @event DraftEvent The event that is triggered after a draft is published.
     * @deprecated in 3.6.0. Use [[EVENT_AFTER_PUBLISH_DRAFT]] instead.
     */
    const EVENT_AFTER_APPLY_DRAFT = self::EVENT_AFTER_PUBLISH_DRAFT;

    /**
     * @var Connection|array|string The database connection to use
     * @since 3.5.4
     */
    public $db = 'db';

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        $this->db = Instance::ensure($this->db, Connection::class);
    }

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
     * @param ElementInterface $source The element to create a draft for
     * @param int $creatorId The user ID that the draft should be attributed to
     * @param string|null $name The draft name
     * @param string|null $notes The draft notes
     * @param array $newAttributes any attributes to apply to the draft
     * @return ElementInterface The new draft
     * @throws \Throwable
     */
    public function createDraft(ElementInterface $source, int $creatorId, string $name = null, string $notes = null, array $newAttributes = []): ElementInterface
    {
        // Make sure the source isn't a draft or revision
        if ($source->getIsDraft() || $source->getIsRevision()) {
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
            $name = $this->generateDraftName($source->id);
        }

        $transaction = $this->db->beginTransaction();
        try {
            // Create the draft row
            $draftId = $this->insertDraftRow($name, $notes, $creatorId, $source->id, $source::trackChanges());

            $newAttributes['draftId'] = $draftId;
            $newAttributes['behaviors']['draft'] = [
                'class' => DraftBehavior::class,
                'sourceId' => $source->id,
                'creatorId' => $creatorId,
                'draftName' => $name,
                'draftNotes' => $notes,
                'trackChanges' => $source::trackChanges(),
            ];

            // Duplicate the element
            $draft = Craft::$app->getElements()->duplicateElement($source, $newAttributes);

            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

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
     * Returns the next auto-generated draft name that should be assigned, for the given source element.
     *
     * @param int $sourceId The source element’s ID
     * @return string
     * @since 3.6.5
     */
    public function generateDraftName(int $sourceId): string
    {
        // Get all of the source's current draft names
        $draftNames = (new Query())
            ->select(['name'])
            ->from([Table::DRAFTS])
            ->where(['sourceId' => $sourceId])
            ->column();
        $draftNames = array_flip($draftNames);

        // Find one that isn't taken
        $num = count($draftNames);
        do {
            $name = Craft::t('app', 'Draft {num}', ['num' => ++$num]);
        } while (isset($draftNames[$name]));

        return $name;
    }

    /**
     * Saves an element as a draft.
     *
     * @param ElementInterface $element
     * @param int|null $creatorId
     * @param string|null $name
     * @param string|null $notes
     * @param bool $markAsSaved
     * @return bool
     * @throws \Throwable
     */
    public function saveElementAsDraft(ElementInterface $element, ?int $creatorId = null, ?string $name = null, ?string $notes = null, bool $markAsSaved = true): bool
    {
        if ($name === null) {
            $name = Craft::t('app', 'First draft');
        }

        // Create the draft row
        $draftId = $this->insertDraftRow($name, $notes, $creatorId);

        $element->draftId = $draftId;
        $element->attachBehavior('draft', new DraftBehavior([
            'creatorId' => $creatorId,
            'draftName' => $name,
            'draftNotes' => $notes,
            'markAsSaved' => $markAsSaved,
        ]));

        // Try to save and return the result
        return Craft::$app->getElements()->saveElement($element);
    }

    /**
     * Merges recent source element changes into a draft.
     *
     * @param ElementInterface $draft The draft
     * @since 3.4.0
     */
    public function mergeSourceChanges(ElementInterface $draft)
    {
        /** @var ElementInterface|DraftBehavior $draft */
        /** @var DraftBehavior $behavior */
        $behavior = $draft->getBehavior('draft');

        if (!$behavior->trackChanges) {
            return;
        }

        $sourceId = $draft->getSourceId();
        if ($sourceId === $draft->id) {
            return;
        }

        $sourceElements = $draft::find()
            ->id($sourceId)
            ->siteId('*')
            ->anyStatus()
            ->ignorePlaceholders()
            ->indexBy('siteId')
            ->all();

        // Make sure the draft actually supports its own site ID
        $supportedSites = ElementHelper::supportedSitesForElement($draft);
        $supportedSiteIds = ArrayHelper::getColumn($supportedSites, 'siteId');
        if (!in_array($draft->siteId, $supportedSiteIds, false)) {
            throw new Exception('Attempting to merge source changes for a draft in an unsupported site.');
        }

        // Fire a 'beforeMergeSource' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_MERGE_SOURCE_CHANGES)) {
            $this->trigger(self::EVENT_BEFORE_MERGE_SOURCE_CHANGES, new DraftEvent([
                'source' => $sourceElements[$draft->siteId] ?? reset($sourceElements),
                'creatorId' => $behavior->creatorId,
                'draftName' => $behavior->draftName,
                'draftNotes' => $behavior->draftNotes,
                'draft' => $draft,
            ]));
        }

        $transaction = $this->db->beginTransaction();
        try {
            // Start with $draft's site
            if (isset($sourceElements[$draft->siteId])) {
                $this->_mergeSourceChangesInternal($sourceElements[$draft->siteId], $draft);
            }

            // Now the other sites
            /** @var ElementInterface[]|DraftBehavior[] $otherSiteDrafts */
            $otherSiteDrafts = $draft::find()
                ->drafts()
                ->id($draft->id)
                ->siteId(ArrayHelper::withoutValue($supportedSiteIds, $draft->id))
                ->anyStatus()
                ->all();

            foreach ($otherSiteDrafts as $otherSiteDraft) {
                if (!isset($sourceElements[$otherSiteDraft->siteId])) {
                    continue;
                }
                $this->_mergeSourceChangesInternal($sourceElements[$otherSiteDraft->siteId], $otherSiteDraft);
            }

            // It's now fully duplicated and propagated
            $behavior->dateLastMerged = new \DateTime();
            $draft->afterPropagate(false);

            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        // Fire an 'afterMergeSource' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_MERGE_SOURCE_CHANGES)) {
            $this->trigger(self::EVENT_AFTER_MERGE_SOURCE_CHANGES, new DraftEvent([
                'source' => $sourceElements[$draft->siteId] ?? reset($sourceElements),
                'creatorId' => $behavior->creatorId,
                'draftName' => $behavior->draftName,
                'draftNotes' => $behavior->draftNotes,
                'draft' => $draft,
            ]));
        }
    }

    /**
     * Merges recent source element changes into a draft for a given site
     *
     * @param ElementInterface $source The source element
     * @param ElementInterface $draft The draft element
     */
    private function _mergeSourceChangesInternal(ElementInterface $source, ElementInterface $draft)
    {
        /** @var ElementInterface|DraftBehavior $draft */
        /** @var DraftBehavior $behavior */
        $behavior = $draft->getBehavior('draft');

        foreach ($behavior->getOutdatedAttributes() as $attribute) {
            if (!$behavior->isAttributeModified($attribute)) {
                $draft->$attribute = $source->$attribute;
            }
        }

        $outdatedFieldHandles = [];
        foreach ($behavior->getOutdatedFields() as $fieldHandle) {
            if (!$behavior->isFieldModified($fieldHandle)) {
                $outdatedFieldHandles[] = $fieldHandle;
            }
        }
        if (!empty($outdatedFieldHandles)) {
            $draft->setFieldValues($source->getSerializedFieldValues($outdatedFieldHandles));
        }

        $behavior->mergingChanges = true;
        Craft::$app->getElements()->saveElement($draft, false, false);
        $behavior->mergingChanges = false;
    }

    /**
     * Publishes a draft.
     *
     * @param ElementInterface $draft The draft
     * @return ElementInterface The updated source element
     * @throws \Throwable
     * @since 3.6.0
     */
    public function publishDraft(ElementInterface $draft): ElementInterface
    {
        /** @var ElementInterface|DraftBehavior $draft */
        /** @var DraftBehavior $behavior */
        $behavior = $draft->getBehavior('draft');
        $source = ElementHelper::sourceElement($draft, true);

        if ($source === null) {
            throw new Exception('Could not find a source element for the draft in any of its supported sites.');
        }

        // If the source ended up being from a different site than the draft, get the draft in that site
        if ($source->siteId != $draft->siteId) {
            $draft = $draft::find()
                ->drafts()
                ->id($draft->id)
                ->siteId($source->siteId)
                ->structureId($source->structureId)
                ->anyStatus()
                ->one();
            if ($draft === null) {
                throw new Exception("Could not load the draft for site ID $source->siteId");
            }
        }

        // Fire a 'beforePublishDraft' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_PUBLISH_DRAFT)) {
            $this->trigger(self::EVENT_BEFORE_PUBLISH_DRAFT, new DraftEvent([
                'source' => $source,
                'creatorId' => $behavior->creatorId,
                'draftName' => $behavior->draftName,
                'draftNotes' => $behavior->draftNotes,
                'draft' => $draft,
            ]));
        }

        $elementsService = Craft::$app->getElements();
        $draftNotes = $draft->draftNotes;

        $transaction = $this->db->beginTransaction();
        try {
            if ($source !== $draft) {
                // Merge in any attribute & field values that were updated in the source element, but not the draft
                $this->mergeSourceChanges($draft);

                // "Duplicate" the draft with the source element's ID, UID, and content ID
                $newSource = $elementsService->duplicateElement($draft, [
                    'id' => $source->id,
                    'uid' => $source->uid,
                    'root' => $source->root,
                    'lft' => $source->lft,
                    'rgt' => $source->rgt,
                    'level' => $source->level,
                    'dateCreated' => $source->dateCreated,
                    'draftId' => null,
                    'revisionNotes' => $draftNotes ?: Craft::t('app', 'Applied “{name}”', ['name' => $draft->draftName]),
                ]);
            } else {
                // Detach the draft behavior
                $behavior = $draft->detachBehavior('draft');

                // Duplicate the draft as a new element
                $e = null;
                try {
                    $newSource = $elementsService->duplicateElement($draft, [
                        'draftId' => null,
                        'revisionNotes' => $draftNotes,
                    ]);
                } catch (\Throwable $e) {
                    // Don't throw it just yet, until we reattach the draft behavior
                }

                // Now reattach the draft behavior to the draft
                if ($behavior !== null) {
                    $draft->attachBehavior('draft', $behavior);
                }

                if ($e !== null) {
                    throw $e;
                }
            }

            // Now delete the draft
            $elementsService->deleteElement($draft, true);

            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();

            if ($e instanceof InvalidElementException) {
                // Add the errors from the duplicated element back onto the draft
                $draft->addErrors($e->element->getErrors());
            }

            throw $e;
        }

        // Fire an 'afterPublishDraft' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_PUBLISH_DRAFT)) {
            $this->trigger(self::EVENT_AFTER_PUBLISH_DRAFT, new DraftEvent([
                'source' => $newSource,
                'creatorId' => $behavior->creatorId,
                'draftName' => $behavior->draftName,
                'draftNotes' => $behavior->draftNotes,
                'draft' => $draft,
            ]));
        }

        return $newSource;
    }

    /**
     * Publishes a draft.
     *
     * @param ElementInterface $draft The draft
     * @return ElementInterface The updated source element
     * @throws \Throwable
     * @deprecated in 3.6.0. Use [[publishDraft()]] instead.
     */
    public function applyDraft(ElementInterface $draft): ElementInterface
    {
        return $this->publishDraft($draft);
    }

    /**
     * Deletes any sourceless drafts that were never formally saved.
     *
     * @return void
     */
    public function purgeUnsavedDrafts(): void
    {
        $generalConfig = Craft::$app->getConfig()->getGeneral();

        if ($generalConfig->purgeUnsavedDraftsDuration === 0) {
            return;
        }

        $interval = DateTimeHelper::secondsToInterval($generalConfig->purgeUnsavedDraftsDuration);
        $expire = DateTimeHelper::currentUTCDateTime();
        $pastTime = $expire->sub($interval);

        $drafts = (new Query())
            ->select(['e.draftId', 'e.type'])
            ->from(['e' => Table::ELEMENTS])
            ->innerJoin(['d' => Table::DRAFTS], '[[d.id]] = [[e.draftId]]')
            ->where(['d.saved' => false])
            ->andWhere(['d.sourceId' => null])
            ->andWhere(['<', 'e.dateUpdated', Db::prepareDateForDb($pastTime)])
            ->all();

        $elementsService = Craft::$app->getElements();

        foreach ($drafts as $draftInfo) {
            /** @var ElementInterface|string $elementType */
            $elementType = $draftInfo['type'];
            $draft = $elementType::find()
                ->draftId($draftInfo['draftId'])
                ->anyStatus()
                ->siteId('*')
                ->one();

            if ($draft) {
                $elementsService->deleteElement($draft, true);
            } else {
                // Perhaps the draft's row in the `entries` table was deleted manually or something.
                // Just drop its row in the `drafts` table, and let that cascade to `elements` and whatever other tables
                // still have rows for the draft.
                Db::delete(Table::DRAFTS, [
                    'id' => $draftInfo['draftId'],
                ], [], $this->db);
            }

            Craft::info("Deleted unsaved draft {$draftInfo['draftId']}", __METHOD__);
        }
    }

    /**
     * Creates a new row in the `drafts` table.
     *
     * @param string|null $name
     * @param string|null $notes
     * @param int|null $creatorId
     * @param int|null $sourceId
     * @param bool $trackChanges
     * @return int The new draft ID
     * @throws DbException
     * @since 3.6.4
     */
    public function insertDraftRow(?string $name, ?string $notes = null, int $creatorId = null, ?int $sourceId = null, bool $trackChanges = false): int
    {
        Db::insert(Table::DRAFTS, [
            'sourceId' => $sourceId,
            'creatorId' => $creatorId,
            'name' => $name,
            'notes' => $notes,
            'trackChanges' => $trackChanges,
        ], false, $this->db);
        return $this->db->getLastInsertID(Table::DRAFTS);
    }
}
