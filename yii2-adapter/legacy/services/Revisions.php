<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use Craft;
use craft\base\ElementInterface;
use craft\events\RevisionEvent;
use CraftCms\Cms\Element\Events\CreatingRevision;
use CraftCms\Cms\Element\Events\RevertedToRevision;
use CraftCms\Cms\Element\Events\RevertingToRevision;
use CraftCms\Cms\Element\Events\RevisionCreated;
use CraftCms\Cms\Element\Exceptions\InvalidElementException;
use Illuminate\Support\Facades\Event;
use Throwable;
use yii\base\Component;

/**
 * Revisions service.
 *
 * An instance of the service is available via [[\craft\base\ApplicationTrait::getRevisions()|`Craft::$app->getRevisions()`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.2.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\Revisions} instead.
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
        return app(\CraftCms\Cms\Element\Revisions::class)->createRevision($canonical, $creatorId, $notes, $newAttributes, $force);
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
        return app(\CraftCms\Cms\Element\Revisions::class)->revertToRevision($revision, $creatorId);
    }

    public static function registerEvents(): void
    {
        foreach ([
            self::EVENT_BEFORE_CREATE_REVISION => CreatingRevision::class,
            self::EVENT_AFTER_CREATE_REVISION => RevisionCreated::class,
            self::EVENT_BEFORE_REVERT_TO_REVISION => RevertingToRevision::class,
            self::EVENT_AFTER_REVERT_TO_REVISION => RevertedToRevision::class,
        ] as $old => $new) {
            Event::listen($new, function(\CraftCms\Cms\Element\Events\RevisionEvent $event) use ($old) {
                if (Craft::$app->getRevisions()->hasEventHandlers($old)) {
                    $yiiEvent = new RevisionEvent([
                        'canonical' => $event->canonical,
                        'creatorId' => $event->creatorId,
                        'revisionNum' => $event->revisionNum,
                        'revisionNotes' => $event->revisionNotes,
                        'revision' => $event->revision,
                    ]);

                    Craft::$app->getRevisions()->trigger($old, $yiiEvent);

                    if (property_exists($event, 'handled')) {
                        $event->handled = $yiiEvent->handled;
                    }

                    $event->canonical = $yiiEvent->canonical;
                    $event->creatorId = $yiiEvent->creatorId;
                    $event->revisionNum = $yiiEvent->revisionNum;
                    $event->revisionNotes = $yiiEvent->revisionNotes;
                    $event->revision = $yiiEvent->revision;
                }
            });
        }
    }
}
