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
use craft\db\Connection;
use craft\db\Query;
use craft\db\Table;
use craft\errors\InvalidElementException;
use craft\events\DraftEvent;
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
 *
 * An instance of the service is available via [[\craft\base\ApplicationTrait::getDrafts()|`Craft::$app->drafts`]].
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
     * @deprecated in 3.7.0. Use [[Elements::EVENT_BEFORE_MERGE_CANONICAL_CHANGES]] instead.
     */
    const EVENT_BEFORE_MERGE_SOURCE_CHANGES = 'beforeMergeSource';

    /**
     * @event DraftEvent The event that is triggered after source changes are merged into a draft.
     * @since 3.4.0
     * @deprecated in 3.7.0. Use [[Elements::EVENT_AFTER_MERGE_CANONICAL_CHANGES]] instead.
     */
    const EVENT_AFTER_MERGE_SOURCE_CHANGES = 'afterMergeSource';

    /**
     * @event DraftEvent The event that is triggered before a draft is applied to its canonical element.
     * @see applyDraft()
     * @since 3.1.0
     */
    const EVENT_BEFORE_APPLY_DRAFT = 'beforeApplyDraft';

    /**
     * @event DraftEvent The event that is triggered after a draft is applied to its canonical element.
     * @see applyDraft()
     * @since 3.1.0
     */
    const EVENT_AFTER_APPLY_DRAFT = 'afterApplyDraft';

    /**
     * @event DraftEvent The event that is triggered before a draft is applied to its canonical element.
     * @since 3.6.0
     * @deprecated in 3.7.0. Use [[EVENT_BEFORE_APPLY_DRAFT]] instead.
     */
    const EVENT_BEFORE_PUBLISH_DRAFT = self::EVENT_BEFORE_APPLY_DRAFT;

    /**
     * @event DraftEvent The event that is triggered after a draft is applied to its canonical element.
     * @since 3.6.0
     * @deprecated in 3.7.0. Use [[EVENT_AFTER_APPLY_DRAFT]] instead.
     */
    const EVENT_AFTER_PUBLISH_DRAFT = self::EVENT_AFTER_APPLY_DRAFT;

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
     * @param bool $provisional Whether to create a provisional draft
     * @return ElementInterface The new draft
     * @throws \Throwable
     */
    public function createDraft(
        ElementInterface $source,
        int $creatorId,
        string $name = null,
        string $notes = null,
        array $newAttributes = [],
        bool $provisional = false
    ): ElementInterface {
        // Make sure the source isn't a draft or revision
        if ($source->getIsDraft() || $source->getIsRevision()) {
            throw new InvalidArgumentException('Cannot create a draft from another draft or revision.');
        }

        // Fire a 'beforeCreateDraft' event
        $event = new DraftEvent([
            'source' => $source,
            'creatorId' => $creatorId,
            'provisional' => $provisional,
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
            $draftId = $this->insertDraftRow($name, $notes, $creatorId, $source->id, $source::trackChanges(), $provisional);

            // Duplicate the element
            $newAttributes['isProvisionalDraft'] = $provisional;
            $newAttributes['canonicalId'] = $source->id;
            $newAttributes['draftId'] = $draftId;
            $newAttributes['behaviors']['draft'] = [
                'class' => DraftBehavior::class,
                'creatorId' => $creatorId,
                'draftName' => $name,
                'draftNotes' => $notes,
                'trackChanges' => $source::trackChanges(),
            ];

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
                'provisional' => $provisional,
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
     * @deprecated in 3.7.0. Use [[Elements::mergeCanonicalChanges()]] instead.
     */
    public function mergeSourceChanges(ElementInterface $draft)
    {
        try {
            Craft::$app->getElements()->mergeCanonicalChanges($draft);
        } catch (InvalidArgumentException $e) {
        }
    }

    /**
     * Applies a draft to its canonical element, and deletes the draft.
     *
     * If an unpublished draft is passed, its draft data will simply be removed from it.
     *
     * @param ElementInterface $draft The draft
     * @return ElementInterface The canonical element with the draft applied to it
     * @throws \Throwable
     * @since 3.6.0
     */
    public function applyDraft(ElementInterface $draft): ElementInterface
    {
        /** @var ElementInterface|DraftBehavior $draft */
        /** @var DraftBehavior $behavior */
        $behavior = $draft->getBehavior('draft');
        $canonical = $draft->getCanonical(true);

        // If the source ended up being from a different site than the draft, get the draft in that site
        if ($canonical->siteId != $draft->siteId) {
            $draft = $draft::find()
                ->drafts()
                ->provisionalDrafts(null)
                ->id($draft->id)
                ->siteId($canonical->siteId)
                ->structureId($canonical->structureId)
                ->anyStatus()
                ->one();
            if ($draft === null) {
                throw new Exception("Could not load the draft for site ID $canonical->siteId");
            }
        }

        // Fire a 'beforeApplyDraft' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_APPLY_DRAFT)) {
            $this->trigger(self::EVENT_BEFORE_APPLY_DRAFT, new DraftEvent([
                'source' => $canonical,
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
            if ($canonical !== $draft) {
                // Merge in any attribute & field values that were updated in the source element, but not the draft
                if (ElementHelper::isOutdated($draft)) {
                    $elementsService->mergeCanonicalChanges($draft);
                }

                // "Duplicate" the draft with the source element's ID, UID, and content ID
                $newSource = $elementsService->updateCanonicalElement($draft, [
                    'revisionNotes' => $draftNotes ?: Craft::t('app', 'Applied “{name}”', ['name' => $draft->draftName]),
                ]);

                // Move the new source element after the draft?
                if ($draft->structureId && $draft->root) {
                    Craft::$app->getStructures()->moveAfter($draft->structureId, $newSource, $draft);
                }

                // Now delete the draft
                $elementsService->deleteElement($draft, true);
            } else {
                // Just remove the draft data
                $draftId = $draft->draftId;
                $draft->draftId = null;
                $draft->detachBehavior('draft');
                $draft->setRevisionNotes($draftNotes);
                $draft->firstSave = true;

                // We still need to validate so the SlugValidator gets run
                $draft->setScenario(Element::SCENARIO_ESSENTIALS);
                $draft->validate();

                // If there are any errors on the URI, re-validate as disabled
                if ($draft->hasErrors('uri') && $draft->enabled) {
                    $draft->enabled = false;
                    $draft->validate();
                }

                try {
                    if ($draft->hasErrors() || !$elementsService->saveElement($draft, false)) {
                        throw new InvalidElementException($draft, 'Draft ' . $draft->id . ' could not be applied because it doesn\'t validate.');
                    }
                    Db::delete(Table::DRAFTS, [
                        'id' => $draftId,
                    ]);
                } catch (\Throwable $e) {
                    // Put everything back
                    $draft->draftId = $draftId;
                    $draft->attachBehavior('draft', $behavior);
                    $draft->firstSave = false;
                    throw $e;
                }

                $draft->firstSave = false;
                $newSource = $draft;
            }

            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();

            if ($e instanceof InvalidElementException && $draft !== $e->element) {
                // Add the errors from the duplicated element back onto the draft
                $draft->addErrors($e->element->getErrors());
            }

            throw $e;
        }

        // Fire an 'afterApplyDraft' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_APPLY_DRAFT)) {
            $this->trigger(self::EVENT_AFTER_APPLY_DRAFT, new DraftEvent([
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
     * Applies a draft to its canonical element, and deletes the draft.
     *
     * If an unpublished draft is passed, its draft data will simply be removed from it.
     *
     * @param ElementInterface $draft The draft
     * @return ElementInterface The canonical element with the draft applied to it
     * @throws \Throwable
     * @deprecated in 3.7.0. Use [[applyDraft()]] instead.
     */
    public function publishDraft(ElementInterface $draft): ElementInterface
    {
        return $this->applyDraft($draft);
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
                ->site('*')
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
     * @param bool $provisional
     * @return int The new draft ID
     * @throws DbException
     * @since 3.6.4
     */
    public function insertDraftRow(
        ?string $name,
        ?string $notes = null,
        int $creatorId = null,
        ?int $sourceId = null,
        bool $trackChanges = false,
        bool $provisional = false
    ): int {
        Db::insert(Table::DRAFTS, [
            'sourceId' => $sourceId, // todo: remove this in v4
            'creatorId' => $creatorId,
            'provisional' => $provisional,
            'name' => $name,
            'notes' => $notes,
            'trackChanges' => $trackChanges,
        ], false, $this->db);
        return $this->db->getLastInsertID(Table::DRAFTS);
    }
}
