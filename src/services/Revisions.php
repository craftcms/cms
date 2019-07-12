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
use craft\behaviors\RevisionBehavior;
use craft\db\Query;
use craft\db\Table;
use craft\elements\Entry;
use craft\errors\InvalidElementException;
use craft\events\RevisionEvent;
use craft\helpers\ArrayHelper;
use craft\helpers\Json;
use yii\base\Component;
use yii\base\InvalidArgumentException;
use yii\db\Exception;

/**
 * Revisions service.
 * An instance of the Revisions service is globally accessible in Craft via [[\craft\base\ApplicationTrait::getRevisions()|`Craft::$app->revisions`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.2
 */
class Revisions extends Component
{
    // Constants
    // =========================================================================

    /**
     * @event DraftEvent The event that is triggered before a revision is created.
     */
    const EVENT_BEFORE_CREATE_REVISION = 'beforeCreateRevision';

    /**
     * @event DraftEvent The event that is triggered after a revision is created.
     */
    const EVENT_AFTER_CREATE_REVISION = 'afterCreateRevision';

    /**
     * @event DraftEvent The event that is triggered before an element is reverted to a revision.
     */
    const EVENT_BEFORE_REVERT_TO_REVISION = 'beforeRevertToRevision';

    /**
     * @event DraftEvent The event that is triggered after an element is reverted to a revision.
     */
    const EVENT_AFTER_REVERT_TO_REVISION = 'afterRevertToRevision';

    // Public Methods
    // =========================================================================

    /**
     * Creates a new revision for the given element.
     *
     * If the element appears to have not changed since its last revision, its last revision will be returned instead.
     *
     * @param ElementInterface $source The element to create a revision for
     * @param int|null $creatorId The user ID that the revision should be attributed to
     * @param string|null $notes The revision notes
     * @param array $newAttributes any attributes to apply to the draft
     * @param bool $force Whether to force a new revision even if the element doesn't appear to have changed since the last revision
     * @return ElementInterface The new revision
     * @throws \Throwable
     */
    public function createRevision(ElementInterface $source, int $creatorId = null, string $notes = null, array $newAttributes = [], bool $force = false): ElementInterface
    {
        // Make sure the source isn't a draft or revision
        /** @var Element $source */
        if ($source->getIsDraft() || $source->getIsRevision()) {
            throw new InvalidArgumentException('Cannot create a revision from another revision or draft.');
        }

        /** @var Element $source */
        $lockKey = 'revision:' . $source->id;
        $mutex = Craft::$app->getMutex();
        if (!$mutex->acquire($lockKey)) {
            throw new Exception('Could not acquire a lock to save a revision for element ' . $source->id);
        }

        $num = ArrayHelper::remove($newAttributes, 'revisionNum');

        if (!$force || !$num) {
            // Find the source's last revision number, if it has one
            $lastRevisionNum = (new Query())
                ->select(['num'])
                ->from([Table::REVISIONS])
                ->where(['sourceId' => $source->id])
                ->orderBy(['num' => SORT_DESC])
                ->limit(1)
                ->scalar();

            if (!$force && $lastRevisionNum) {
                // Get the revision, if it exists for the source's site
                /** @var Element|RevisionBehavior|null $lastRevision */
                $lastRevision = $source::find()
                    ->revisionOf($source)
                    ->siteId($source->siteId)
                    ->anyStatus()
                    ->andWhere(['revisions.num' => $lastRevisionNum])
                    ->one();

                // If the source hasn't been updated since the revision's creation date,
                // there's no need to create a new one
                if ($lastRevision && $source->dateUpdated->getTimestamp() === $lastRevision->dateCreated->getTimestamp()) {
                    $mutex->release($lockKey);
                    return $lastRevision;
                }
            }

            // Get the next revision number for this element
            $num = ($lastRevisionNum ?: 0) + 1;
        }

        if ($creatorId === null) {
            // Default to the logged-in user ID if there is one
            $creatorId = Craft::$app->getUser()->getId();
        }

        // Fire a 'beforeCreateRevision' event
        $event = new RevisionEvent([
            'source' => $source,
            'creatorId' => $creatorId,
            'revisionNum' => $num,
            'revisionNotes' => $notes,
        ]);
        $this->trigger(self::EVENT_BEFORE_CREATE_REVISION, $event);
        $notes = $event->revisionNotes;

        $elementsService = Craft::$app->getElements();

        $transaction = Craft::$app->getDb()->beginTransaction();
        try {
            // Create the revision row
            $db = Craft::$app->getDb();
            $db->createCommand()
                ->insert(Table::REVISIONS, [
                    'sourceId' => $source->id,
                    'creatorId' => $creatorId,
                    'num' => $num,
                    'notes' => $notes,
                ], false)
                ->execute();

            $newAttributes['revisionId'] = $db->getLastInsertID(Table::REVISIONS);
            $newAttributes['behaviors']['revision'] = [
                'class' => RevisionBehavior::class,
                'sourceId' => $source->id,
                'creatorId' => $creatorId,
                'revisionNum' => $num,
                'revisionNotes' => $notes,
            ];

            if (!isset($newAttributes['dateCreated'])) {
                $newAttributes['dateCreated'] = $source->dateUpdated;
            }

            // Duplicate the element
            /** @var Element $revision */
            $revision = $elementsService->duplicateElement($source, $newAttributes);

            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();
            $mutex->release($lockKey);
            throw $e;
        }

        // Fire an 'afterCreateRevision' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_CREATE_REVISION)) {
            $this->trigger(self::EVENT_AFTER_CREATE_REVISION, new RevisionEvent([
                'source' => $source,
                'creatorId' => $creatorId,
                'revisionNum' => $num,
                'revisionNotes' => $notes,
                'revision' => $revision,
            ]));
        }

        $mutex->release($lockKey);

        // Prune any excess revisions
        $maxRevisions = Craft::$app->getConfig()->getGeneral()->maxRevisions;
        if ($maxRevisions > 0) {
            // Don't count the current revision
            $extraRevisions = $source::find()
                ->revisionOf($source)
                ->siteId($source->siteId)
                ->anyStatus()
                ->orderBy(['num' => SORT_DESC])
                ->offset($maxRevisions + 1)
                ->all();

            foreach ($extraRevisions as $extraRevision) {
                $elementsService->deleteElement($extraRevision, true);
            }
        }

        return $revision;
    }

    /**
     * Reverts an element to a revision, and creates a new revision for the element.
     *
     * @param ElementInterface $revision The revision whose source element should be reverted to
     * @param int $creatorId The user ID that the new revision should be attributed to
     * @return ElementInterface The new source element
     * @throws InvalidElementException
     * @throws \Throwable
     */
    public function revertToRevision(ElementInterface $revision, int $creatorId): ElementInterface
    {
        /** @var Element|RevisionBehavior $revision */
        /** @var Element $source */
        $source = $revision->getSource();

        // Fire a 'beforeRevertToRevision' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_REVERT_TO_REVISION)) {
            $this->trigger(self::EVENT_BEFORE_REVERT_TO_REVISION, new RevisionEvent([
                'source' => $source,
                'creatorId' => $creatorId,
                'revisionNum' => $revision->revisionNum,
                'revisionNotes' => $revision->revisionNotes,
                'revision' => $revision,
            ]));
        }

        $transaction = Craft::$app->getDb()->beginTransaction();
        try {
            // "Duplicate" the revision with the source element's ID, UID, and content ID
            $newSource = Craft::$app->getElements()->duplicateElement($revision, [
                'id' => $source->id,
                'uid' => $source->uid,
                'revisionId' => null,
                'revisionCreatorId' => $creatorId,
                'revisionNotes' => Craft::t('app', 'Reverted to revision {num}.', ['num' => $revision->revisionNum]),
            ]);

            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        // Fire an 'afterRevertToRevision' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_REVERT_TO_REVISION)) {
            $this->trigger(self::EVENT_AFTER_REVERT_TO_REVISION, new RevisionEvent([
                'source' => $newSource,
                'creatorId' => $creatorId,
                'revisionNum' => $revision->revisionNum,
                'revisionNotes' => $revision->revisionNotes,
                'revision' => $revision,
            ]));
        }

        return $newSource;
    }
}
