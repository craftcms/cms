<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use Craft;
use craft\events\DraftEvent;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Element\Events\DraftApplied;
use CraftCms\Cms\Element\Events\DraftApplying;
use CraftCms\Cms\Element\Events\DraftCreated;
use CraftCms\Cms\Element\Events\DraftCreating;
use CraftCms\Cms\Element\Exceptions\InvalidElementException;
use Illuminate\Support\Facades\Event;
use Throwable;
use yii\base\Component;
use yii\db\Exception as DbException;
use yii\di\Instance;
use function CraftCms\Cms\t;

/**
 * Drafts service.
 *
 * An instance of the service is available via [[\craft\base\ApplicationTrait::getDrafts()|`Craft::$app->getDrafts()`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.2.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\Drafts} instead.
 */
class Drafts extends Component
{
    /**
     * @event DraftEvent The event that is triggered before a draft is created.
     */
    public const EVENT_BEFORE_CREATE_DRAFT = 'beforeCreateDraft';

    /**
     * @event DraftEvent The event that is triggered after a draft is created.
     */
    public const EVENT_AFTER_CREATE_DRAFT = 'afterCreateDraft';

    /**
     * @event DraftEvent The event that is triggered before a draft is published.
     * @since 3.6.0
     */
    public const EVENT_BEFORE_APPLY_DRAFT = 'beforeApplyDraft';

    /**
     * @event DraftEvent The event that is triggered after a draft is applied to its canonical element.
     * @see applyDraft()
     */
    public const EVENT_AFTER_APPLY_DRAFT = 'afterApplyDraft';

    /**
     * Returns drafts for a given element ID that the current user is allowed to edit
     *
     * @param ElementInterface $element
     * @param string|null $permission
     * @return ElementInterface[]
     */
    public function getEditableDrafts(ElementInterface $element, ?string $permission = null): array
    {
        return app(\CraftCms\Cms\Element\Drafts::class)->getEditableDrafts($element, $permission)->all();
    }

    /**
     * Creates a new draft for the given element.
     *
     * @template T of ElementInterface
     * @param T $canonical The element to create a draft for
     * @param int|null $creatorId The user ID that the draft should be attributed to
     * @param string|null $name The draft name
     * @param string|null $notes The draft notes
     * @param array $newAttributes any attributes to apply to the draft
     * @param bool $provisional Whether to create a provisional draft
     * @return T The new draft
     * @throws Throwable
     */
    public function createDraft(
        ElementInterface $canonical,
        ?int $creatorId = null,
        ?string $name = null,
        ?string $notes = null,
        array $newAttributes = [],
        bool $provisional = false,
    ): ElementInterface {
        return app(\CraftCms\Cms\Element\Drafts::class)->createDraft($canonical, $creatorId, $name, $notes, $newAttributes, $provisional);
    }

    /**
     * Returns the next auto-generated draft name that should be assigned, for the given canonical element.
     *
     * @param int $canonicalId The canonical element’s ID
     * @return string
     * @since 3.6.5
     */
    public function generateDraftName(int $canonicalId): string
    {
        return app(\CraftCms\Cms\Element\Drafts::class)->generateDraftName($canonicalId);
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
     * @throws Throwable
     */
    public function saveElementAsDraft(ElementInterface $element, ?int $creatorId = null, ?string $name = null, ?string $notes = null, bool $markAsSaved = true): bool
    {
        return app(\CraftCms\Cms\Element\Drafts::class)->saveElementAsDraft($element, $creatorId, $name, $notes, $markAsSaved);
    }

    /**
     * Applies a draft to its canonical element, and deletes the draft.
     *
     * If an unpublished draft is passed, its draft data will simply be removed from it.
     *
     * @template T of ElementInterface
     * @param T $draft The draft
     * @param array $newAttributes Any attributes to apply to the canonical element
     * @return T The canonical element with the draft applied to it
     * @throws Throwable
     * @since 3.6.0
     */
    public function applyDraft(ElementInterface $draft, array $newAttributes = []): ElementInterface
    {
        return app(\CraftCms\Cms\Element\Drafts::class)->applyDraft($draft, $newAttributes);
    }

    /**
     * Removes draft data from the given draft.
     *
     * @param ElementInterface $draft
     * @throws InvalidElementException
     * @since 4.0.0
     */
    public function removeDraftData(ElementInterface $draft): void
    {
        app(\CraftCms\Cms\Element\Drafts::class)->removeDraftData($draft);
    }

    /**
     * Deletes any unpublished drafts that were never formally saved.
     */
    public function purgeUnsavedDrafts(): void
    {
        app(\CraftCms\Cms\Element\Drafts::class)->purgeUnsavedDrafts();
    }

    /**
     * Creates a new row in the `drafts` table.
     *
     * @param string|null $name
     * @param string|null $notes
     * @param int|null $creatorId
     * @param int|null $canonicalId
     * @param bool $trackChanges
     * @param bool $provisional
     * @return int The new draft ID
     * @throws DbException
     * @since 3.6.4
     */
    public function insertDraftRow(
        ?string $name,
        ?string $notes = null,
        ?int $creatorId = null,
        ?int $canonicalId = null,
        bool $trackChanges = false,
        bool $provisional = false,
    ): int {
        return app(\CraftCms\Cms\Element\Drafts::class)->insertDraftRow($name, $notes, $creatorId, $canonicalId, $trackChanges, $provisional);
    }

    public static function registerEvents(): void
    {
        foreach ([
            self::EVENT_BEFORE_CREATE_DRAFT => DraftCreating::class,
            self::EVENT_AFTER_CREATE_DRAFT => DraftCreated::class,
            self::EVENT_BEFORE_APPLY_DRAFT => DraftApplying::class,
            self::EVENT_AFTER_APPLY_DRAFT => DraftApplied::class,
        ] as $old => $new) {
            Event::listen($new, function(\CraftCms\Cms\Element\Events\DraftEvent $event) use ($old) {
                if (Craft::$app->getDrafts()->hasEventHandlers($old)) {
                    $yiiEvent = new DraftEvent([
                        'canonical' => $event->canonical,
                        'creatorId' => $event->creatorId,
                        'provisional' => $event->provisional,
                        'draftName' => $event->draftName,
                        'draftNotes' => $event->draftNotes,
                        'draft' => $event->draft,
                    ]);

                    Craft::$app->getDrafts()->trigger($old, $yiiEvent);

                    $event->canonical = $yiiEvent->canonical;
                    $event->creatorId = $yiiEvent->creatorId;
                    $event->provisional = $yiiEvent->provisional;
                    $event->draftName = $yiiEvent->draftName;
                    $event->draftNotes = $yiiEvent->draftNotes;
                    $event->draft = $yiiEvent->draft;
                }
            });
        }
    }
}
