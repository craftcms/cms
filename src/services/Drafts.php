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
     * @event DraftEvent The event that is triggered before a draft is applied to its source element.
     */
    const EVENT_BEFORE_APPLY_DRAFT = 'beforeApplyDraft';

    /**
     * @event DraftEvent The event that is triggered after a draft is applied to its source element.
     */
    const EVENT_AFTER_APPLY_DRAFT = 'afterApplyDraft';

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
        /** @var Element $source */
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
            $draftNames = (new Query())
                ->select(['name'])
                ->from([Table::DRAFTS])
                ->where(['sourceId' => $source->id])
                ->column();
            $draftNames = array_flip($draftNames);
            $num = count($draftNames);
            do {
                $name = Craft::t('app', 'Draft {num}', ['num' => ++$num]);
            } while (isset($draftNames[$name]));
        }

        $transaction = Craft::$app->getDb()->beginTransaction();
        try {
            // Create the draft row
            $draftId = $this->_insertDraftRow($source->id, $creatorId, $name, $notes, $source::trackChanges());

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
            /** @var Element $draft */
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
     * Saves an element as a draft.
     *
     * @param ElementInterface $element
     * @param int $creatorId
     * @param string|null $name
     * @param string|null $notes
     * @return bool
     * @throws \Throwable
     */
    public function saveElementAsDraft(ElementInterface $element, int $creatorId, string $name = null, string $notes = null): bool
    {
        if ($name === null) {
            $name = 'First draft';
        }

        // Create the draft row
        $draftId = $this->_insertDraftRow(null, $creatorId, $name, $notes);

        /** @var Element $element */
        $element->draftId = $draftId;
        $element->attachBehavior('draft', new DraftBehavior([
            'creatorId' => $creatorId,
            'draftName' => $name,
            'draftNotes' => $notes,
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
        /** @var Element|DraftBehavior $draft */
        /** @var DraftBehavior $behavior */
        $behavior = $draft->getBehavior('draft');

        if (!$behavior->trackChanges) {
            return;
        }

        $sourceId = $draft->getSourceId();
        if ($sourceId === $draft->id) {
            return;
        }

        /** @var Element[] $sourceElements */
        $sourceElements = $draft::find()
            ->id($sourceId)
            ->siteId('*')
            ->anyStatus()
            ->ignorePlaceholders()
            ->indexBy('siteId')
            ->all();

        // Make sure a source element exists for the draft's current site ID
        if (!isset($sourceElements[$draft->siteId])) {
            throw new Exception('Attempting to merge source changes for a draft in an unsupported site.');
        }

        // Make sure the draft actually supports its own site ID
        $supportedSites = ElementHelper::supportedSitesForElement($draft);
        $supportedSiteIds = ArrayHelper::getColumn($supportedSites, 'siteId');
        if (!in_array($draft->siteId, $supportedSiteIds, false)) {
            throw new Exception('Attempting to merge source changes for a draft in an unsupported site.');
        }

        // Fire a 'beforeMergeSource' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_MERGE_SOURCE_CHANGES)) {
            $this->trigger(self::EVENT_BEFORE_MERGE_SOURCE_CHANGES, new DraftEvent([
                'source' => $sourceElements[$draft->siteId],
                'creatorId' => $behavior->creatorId,
                'draftName' => $behavior->draftName,
                'draftNotes' => $behavior->draftNotes,
                'draft' => $draft,
            ]));
        }

        $transaction = Craft::$app->getDb()->beginTransaction();
        try {
            // Start with $draft's site
            $this->_mergeSourceChangesInternal($sourceElements[$draft->siteId], $draft);

            // Now the other sites
            /** @var ElementInterface[]|Element[]|DraftBehavior[] $otherSiteDrafts */
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
                'source' => $sourceElements[$draft->siteId],
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
        /** @var Element|DraftBehavior $draft */
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
     * Applies a draft onto its source element.
     *
     * @param ElementInterface $draft The draft
     * @return ElementInterface The updated source element
     * @throws \Throwable
     */
    public function applyDraft(ElementInterface $draft): ElementInterface
    {
        /** @var Element|DraftBehavior $draft */
        /** @var DraftBehavior $behavior */
        $behavior = $draft->getBehavior('draft');
        /** @var Element $source */
        $source = ElementHelper::sourceElement($draft);

        // Fire a 'beforeApplyDraft' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_APPLY_DRAFT)) {
            $this->trigger(self::EVENT_BEFORE_APPLY_DRAFT, new DraftEvent([
                'source' => $source,
                'creatorId' => $behavior->creatorId,
                'draftName' => $behavior->draftName,
                'draftNotes' => $behavior->draftNotes,
                'draft' => $draft,
            ]));
        }

        $elementsService = Craft::$app->getElements();

        $transaction = Craft::$app->getDb()->beginTransaction();
        try {
            if ($source !== $draft) {
                // Merge in any attribute & field values that were updated in the source element, but not the draft
                $this->mergeSourceChanges($draft);

                // "Duplicate" the draft with the source element's ID, UID, and content ID
                $newSource = $elementsService->duplicateElement($draft, [
                    'id' => $source->id,
                    'uid' => $source->uid,
                    'dateCreated' => $source->dateCreated,
                    'draftId' => null,
                    'revisionNotes' => $draft->draftNotes ?: Craft::t('app', 'Applied “{name}”', ['name' => $draft->draftName]),
                ]);
            } else {
                // Detach the draft behavior
                $behavior = $draft->detachBehavior('draft');

                // Duplicate the draft as a new element
                $e = null;
                try {
                    $newSource = $elementsService->duplicateElement($draft, [
                        'draftId' => null,
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
     * Deletes any sourceless drafts that were never formally saved.
     *
     * This method will check the
     * [[\craft\config\GeneralConfig::purgeUnsavedDraftsDuration|purgeUnsavedDraftsDuration]] config
     * setting, and if it is set to a valid duration, it will delete any sourceless drafts that were created that
     * duration ago, and have still not been formally saved.
     */
    public function purgeUnsavedDrafts()
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
            ->innerJoin(Table::DRAFTS . ' d', '[[d.id]] = [[e.draftId]]')
            ->where(['d.sourceId' => null])
            ->andWhere(['<', 'e.dateCreated', Db::prepareDateForDb($pastTime)])
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
            $elementsService->deleteElement($draft, true);
            Craft::info("Just deleted unsaved draft ID {$draftInfo['draftId']}", __METHOD__);
        }
    }

    /**
     * Creates a new row in the `drafts` table.
     *
     * @param int|null $sourceId
     * @param int $creatorId
     * @param string|null $name
     * @param string|null $notes
     * @param bool $trackChanges
     * @return int The new draft ID
     * @throws DbException
     */
    private function _insertDraftRow(int $sourceId = null, int $creatorId, string $name = null, string $notes = null, bool $trackChanges = false): int
    {
        $db = Craft::$app->getDb();
        $db->createCommand()
            ->insert(Table::DRAFTS, [
                'sourceId' => $sourceId,
                'creatorId' => $creatorId,
                'name' => $name,
                'notes' => $notes,
                'trackChanges' => $trackChanges,
            ], false)
            ->execute();
        return $db->getLastInsertID(Table::DRAFTS);
    }
}
