<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use Craft;
use craft\base\ElementInterface;
use craft\behaviors\RevisionBehavior;
use craft\errors\InvalidElementException;
use craft\errors\MutexException;
use craft\events\RevisionEvent;
use craft\helpers\Queue;
use craft\queue\jobs\PruneRevisions;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Throwable;
use Tpetry\QueryExpressions\Language\Alias;
use yii\base\Component;
use yii\base\InvalidArgumentException;
use function CraftCms\Cms\t;

/**
 * Revisions service.
 *
 * An instance of the service is available via [[\craft\base\ApplicationTrait::getRevisions()|`Craft::$app->getRevisions()`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.2.0
 */
class Revisions extends Component
{
    /**
     * @event RevisionEvent The event that is triggered before a revision is created.
     *
     * You may set [[\yii\base\ModelEvent::$handled]] to `true` to prevent the revision from getting created, but
     * only if at least one revision for the element already exists.
     */
    public const EVENT_BEFORE_CREATE_REVISION = 'beforeCreateRevision';

    /**
     * @event RevisionEvent The event that is triggered after a revision is created.
     */
    public const EVENT_AFTER_CREATE_REVISION = 'afterCreateRevision';

    /**
     * @event RevisionEvent The event that is triggered before an element is reverted to a revision.
     */
    public const EVENT_BEFORE_REVERT_TO_REVISION = 'beforeRevertToRevision';

    /**
     * @event RevisionEvent The event that is triggered after an element is reverted to a revision.
     */
    public const EVENT_AFTER_REVERT_TO_REVISION = 'afterRevertToRevision';

    /**
     * Creates a new revision for the given element and returns its ID.
     *
     * If the element appears to have not changed since its last revision, its last revision’s ID will be returned instead.
     *
     * @param ElementInterface $canonical The element to create a revision for
     * @param int|null $creatorId The user ID that the revision should be attributed to
     * @param string|null $notes The revision notes
     * @param array $newAttributes any attributes to apply to the draft
     * @param bool $force Whether to force a new revision even if the element doesn't appear to have changed since the last revision
     * @return int The revision ID
     * @throws Throwable
     */
    public function createRevision(
        ElementInterface $canonical,
        ?int $creatorId = null,
        ?string $notes = null,
        array $newAttributes = [],
        bool $force = false,
    ): int {
        // Make sure the source isn't a draft or revision
        if ($canonical->getIsDraft() || $canonical->getIsRevision()) {
            throw new InvalidArgumentException('Cannot create a revision from another revision or draft.');
        }

        $lockKey = 'revision:' . $canonical->id;
        $mutex = Cache::lock($lockKey, 3);
        if (!$mutex->get()) {
            throw new MutexException($lockKey, sprintf('Could not acquire a lock to save a revision for element %s', $canonical->id));
        }

        $num = Arr::pull($newAttributes, 'revisionNum');
        $lastRevisionInfo = null;

        try {
            if (!$force || !$num) {
                $lastRevisionInfo = DB::useWriteConnectionWhenReading()
                    ->table(Table::ELEMENTS, 'e')
                    ->select(['e.id', 'r.num', 'e.dateCreated'])
                    ->join(new Alias(Table::REVISIONS, 'r'), 'r.id', 'e.revisionId')
                    ->where('r.canonicalId', $canonical->id)
                    ->orderByDesc('r.num')
                    ->first();

                if (
                    !$force &&
                    $lastRevisionInfo &&
                    Date::parse($lastRevisionInfo->dateCreated)->getTimestamp() === $canonical->dateUpdated->getTimestamp() &&
                    // Make sure all its data is in-tact
                    $canonical::find()->id($lastRevisionInfo->id)->revisions()->status(null)->siteId($canonical->siteId)->exists()
                ) {
                    // The canonical element hasn't been updated since the last revision's creation date,
                    // so there's no need to create a new one
                    return $lastRevisionInfo->id;
                }

                // Get the next revision number for this element
                if ($lastRevisionInfo) {
                    $num = $lastRevisionInfo->num + 1;
                } else {
                    $num = 1;
                }
            }

            if ($creatorId === null) {
                // Default to the logged-in user ID if there is one
                $creatorId = Craft::$app->getUser()->getId();
            }

            // Fire a 'beforeCreateRevision' event
            if ($this->hasEventHandlers(self::EVENT_BEFORE_CREATE_REVISION)) {
                $event = new RevisionEvent([
                    'canonical' => $canonical,
                    'creatorId' => $creatorId,
                    'revisionNum' => $num,
                    'revisionNotes' => $notes,
                ]);
                $this->trigger(self::EVENT_BEFORE_CREATE_REVISION, $event);

                // only bail early if we have at least one revision
                if ($event->handled && $lastRevisionInfo) {
                    return $lastRevisionInfo->id;
                }

                $notes = $event->revisionNotes;
                $creatorId = $event->creatorId;
                $canonical = $event->canonical;
            }

            $elementsService = Craft::$app->getElements();

            DB::beginTransaction();
            try {
                // Create the revision row
                $newAttributes['revisionId'] = DB::table(Table::REVISIONS)
                    ->insertGetId([
                        'canonicalId' => $canonical->id,
                        'creatorId' => $creatorId,
                        'num' => $num,
                        'notes' => $notes,
                    ]);

                // Duplicate the element
                $newAttributes['canonicalId'] = $canonical->id;
                $newAttributes['behaviors']['revision'] = [
                    'class' => RevisionBehavior::class,
                    'creatorId' => $creatorId,
                    'revisionNum' => $num,
                    'revisionNotes' => $notes,
                ];

                if (!isset($newAttributes['dateCreated'])) {
                    $newAttributes['dateCreated'] = $canonical->dateUpdated;
                }

                $revision = $elementsService->duplicateElement($canonical, $newAttributes);

                DB::commit();
            } catch (Throwable $e) {
                DB::rollBack();
                throw $e;
            }

            // Fire an 'afterCreateRevision' event
            if ($this->hasEventHandlers(self::EVENT_AFTER_CREATE_REVISION)) {
                $this->trigger(self::EVENT_AFTER_CREATE_REVISION, new RevisionEvent([
                    'canonical' => $canonical,
                    'creatorId' => $creatorId,
                    'revisionNum' => $num,
                    'revisionNotes' => $notes,
                    'revision' => $revision,
                ]));
            }
        } finally {
            $mutex->release();
        }

        // Prune any excess revisions
        if (Cms::config()->maxRevisions) {
            Queue::push(new PruneRevisions([
                'elementType' => get_class($canonical),
                'canonicalId' => $canonical->id,
                'siteId' => $canonical->siteId,
            ]), 2049);
        }

        return $revision->id;
    }

    /**
     * Reverts an element to a revision, and creates a new revision for the element.
     *
     * @param ElementInterface $revision The revision whose source element should be reverted to
     * @param int $creatorId The user ID that the new revision should be attributed to
     * @return ElementInterface The new source element
     * @throws InvalidElementException
     * @throws Throwable
     */
    public function revertToRevision(ElementInterface $revision, int $creatorId): ElementInterface
    {
        /** @var ElementInterface&RevisionBehavior $revision */
        $canonical = $revision->getCanonical();

        // Fire a 'beforeRevertToRevision' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_REVERT_TO_REVISION)) {
            $this->trigger(self::EVENT_BEFORE_REVERT_TO_REVISION, new RevisionEvent([
                'canonical' => $canonical,
                'creatorId' => $creatorId,
                'revisionNum' => $revision->revisionNum,
                'revisionNotes' => $revision->revisionNotes,
                'revision' => $revision,
            ]));
        }

        // "Duplicate" the revision with the source element’s ID and UID
        $newSource = Craft::$app->getElements()->updateCanonicalElement($revision, [
            'revisionCreatorId' => $creatorId,
            'revisionNotes' => t('Reverted content from revision {num}.', ['num' => $revision->revisionNum]),
        ]);

        // Fire an 'afterRevertToRevision' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_REVERT_TO_REVISION)) {
            $this->trigger(self::EVENT_AFTER_REVERT_TO_REVISION, new RevisionEvent([
                'canonical' => $newSource,
                'creatorId' => $creatorId,
                'revisionNum' => $revision->revisionNum,
                'revisionNotes' => $revision->revisionNotes,
                'revision' => $revision,
            ]));
        }

        return $newSource;
    }
}
