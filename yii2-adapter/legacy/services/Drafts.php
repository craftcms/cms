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
use craft\errors\InvalidElementException;
use craft\events\DraftEvent;
use craft\helpers\DateTimeHelper;
use craft\helpers\ElementHelper;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Support\Arr;
use Illuminate\Support\Facades\DB;
use Throwable;
use Tpetry\QueryExpressions\Language\Alias;
use yii\base\Component;
use yii\base\Exception;
use yii\base\InvalidArgumentException;
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
     * @var Connection|array|string The database connection to use
     * @since 3.5.4
     */
    public string|array|Connection $db = 'db';

    /**
     * @inheritdoc
     */
    public function init(): void
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
    public function getEditableDrafts(ElementInterface $element, ?string $permission = null): array
    {
        $user = Craft::$app->getUser()->getIdentity();
        if (!$user) {
            return [];
        }

        $query = $element::find()
            ->draftOf($element)
            ->siteId($element->siteId)
            ->status(null)
            ->orderBy(['dateUpdated' => SORT_DESC]);

        if (!$permission || !$user->can($permission)) {
            $query->draftCreator($user);
        }

        return $query->all();
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
        // Make sure the canonical element isn't a draft or revision
        if ($canonical->getIsDraft() || $canonical->getIsRevision()) {
            throw new InvalidArgumentException('Cannot create a draft from another draft or revision.');
        }

        $markAsSaved = Arr::pull($newAttributes, 'markAsSaved', true);

        // Fire a 'beforeCreateDraft' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_CREATE_DRAFT)) {
            $event = new DraftEvent([
                'canonical' => $canonical,
                'creatorId' => $creatorId,
                'provisional' => $provisional,
                'draftName' => $name,
                'draftNotes' => $notes,
            ]);
            $this->trigger(self::EVENT_BEFORE_CREATE_DRAFT, $event);
            $name = $event->draftName;
            $notes = $event->draftNotes;
        }

        if ($name === null || $name === '') {
            $name = $this->generateDraftName($canonical->id);
        }

        DB::beginTransaction();

        try {
            // Create the draft row
            $draftId = $this->insertDraftRow($name, $notes, $creatorId, $canonical->id, $canonical::trackChanges(), $provisional);

            // Duplicate the element
            $newAttributes['isProvisionalDraft'] = $provisional;
            $newAttributes['canonicalId'] = $canonical->id;
            $newAttributes['draftId'] = $draftId;
            $newAttributes['behaviors']['draft'] = [
                'class' => DraftBehavior::class,
                'creatorId' => $creatorId,
                'draftName' => $name,
                'draftNotes' => $notes,
                'trackChanges' => $canonical::trackChanges(),
                'markAsSaved' => $markAsSaved,
            ];

            $draft = Craft::$app->getElements()->duplicateElement($canonical, $newAttributes);

            // Duplicate nested element ownership
            DB::table(Table::ELEMENTS_OWNERS)
                ->insertUsing(['elementId', 'ownerId', 'sortOrder'],
                    DB::table(Table::ELEMENTS_OWNERS, 'o')
                        ->select('o.elementId', DB::raw($draft->id), 'o.sortOrder')
                        ->where('o.ownerId', $canonical->id)
                );

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        // Fire an 'afterCreateDraft' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_CREATE_DRAFT)) {
            $this->trigger(self::EVENT_AFTER_CREATE_DRAFT, new DraftEvent([
                'canonical' => $canonical,
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
     * Returns the next auto-generated draft name that should be assigned, for the given canonical element.
     *
     * @param int $canonicalId The canonical element’s ID
     * @return string
     * @since 3.6.5
     */
    public function generateDraftName(int $canonicalId): string
    {
        // Get all of the canonical element’s current draft names
        $draftNames = DB::table(Table::DRAFTS)
            ->where('canonicalId', $canonicalId)
            ->pluck('name')
            ->flip();

        // Find one that isn't taken
        $num = count($draftNames);
        do {
            $name = t('Draft {num}', ['num' => ++$num]);
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
     * @throws Throwable
     */
    public function saveElementAsDraft(ElementInterface $element, ?int $creatorId = null, ?string $name = null, ?string $notes = null, bool $markAsSaved = true): bool
    {
        if ($name === null) {
            $name = t('First draft');
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
        /** @var ElementInterface&DraftBehavior $draft */
        /** @var DraftBehavior $behavior */
        $behavior = $draft->getBehavior('draft');
        $canonical = $draft->getCanonical(true);
        $originalDraft = $draft;

        // If the canonical element ended up being from a different site than the draft, get the draft in that site
        if ($canonical->siteId != $draft->siteId) {
            $draft = $draft::find()
                ->drafts()
                ->provisionalDrafts(null)
                ->id($draft->id)
                ->siteId($canonical->siteId)
                ->structureId($canonical->structureId)
                ->status(null)
                ->one();
            if ($draft === null) {
                throw new Exception("Could not load the draft for site ID $canonical->siteId");
            }
        }

        // Fire a 'beforeApplyDraft' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_APPLY_DRAFT)) {
            $this->trigger(self::EVENT_BEFORE_APPLY_DRAFT, new DraftEvent([
                'canonical' => $canonical,
                'creatorId' => $behavior->creatorId,
                'draftName' => $behavior->draftName,
                'draftNotes' => $behavior->draftNotes,
                'draft' => $draft,
            ]));
        }

        $elementsService = Craft::$app->getElements();
        $draftNotes = $draft->draftNotes;

        DB::beginTransaction();
        try {
            if ($canonical !== $draft) {
                // Merge in any attribute & field values that were updated in the canonical element, but not the draft
                if ($draft::trackChanges() && ElementHelper::isOutdated($draft)) {
                    $elementsService->mergeCanonicalChanges($draft);
                }

                // "Duplicate" the draft with the canonical element’s ID and UID
                $newCanonical = $elementsService->updateCanonicalElement($draft, array_merge($newAttributes, [
                    'revisionNotes' => $draftNotes ?: t('Applied “{name}”', ['name' => $draft->draftName]),
                ]));

                // Move the new canonical element after the draft?
                if ($draft->structureId && $draft->root) {
                    Craft::$app->getStructures()->moveAfter($draft->structureId, $newCanonical, $draft);
                }

                // Now delete the draft
                $elementsService->deleteElement($draft, true);
            } else {
                // Just remove the draft data
                $draft->setRevisionNotes($draftNotes);
                $this->removeDraftData($draft);
                $newCanonical = $draft;
            }

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();

            if ($e instanceof InvalidElementException && $draft !== $e->element) {
                // Add the errors from the duplicated element back onto the draft
                $draft->addErrors($e->element->getErrors());
            }

            throw $e;
        }

        // Fire an 'afterApplyDraft' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_APPLY_DRAFT)) {
            $this->trigger(self::EVENT_AFTER_APPLY_DRAFT, new DraftEvent([
                'canonical' => $newCanonical,
                'creatorId' => $behavior->creatorId,
                'draftName' => $behavior->draftName,
                'draftNotes' => $behavior->draftNotes,
                'draft' => $draft,
            ]));
        }

        // if we were on another site when the applyDraft was triggered,
        // ensure we return the canonical element for the site we were on
        if ($newCanonical->siteId !== $originalDraft->siteId) {
            $newCanonical = $originalDraft->getCanonical();
        }

        return $newCanonical;
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
        /** @var DraftBehavior $behavior */
        $behavior = $draft->getBehavior('draft');
        $draftId = $draft->draftId;

        $draft->draftId = null;
        $draft->detachBehavior('draft');
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
            // no need to propagate or save content here – and it could end up overriding any
            // content changes made to other sites from a previous onAfterPropagate(), etc.
            if ($draft->hasErrors() || !Craft::$app->getElements()->saveElement($draft, false, false)) {
                throw new InvalidElementException($draft, "Draft $draft->id could not be applied because it doesn't validate.");
            }

            DB::table(Table::DRAFTS)->delete($draftId);
        } catch (Throwable $e) {
            // Put everything back
            $draft->draftId = $draftId;
            $draft->attachBehavior('draft', $behavior);
            $draft->firstSave = false;
            throw $e;
        }

        $draft->firstSave = false;
    }

    /**
     * Deletes any unpublished drafts that were never formally saved.
     */
    public function purgeUnsavedDrafts(): void
    {
        $generalConfig = app(GeneralConfig::class);

        if ($generalConfig->purgeUnsavedDraftsDuration === 0) {
            return;
        }

        $interval = DateTimeHelper::secondsToInterval($generalConfig->purgeUnsavedDraftsDuration);
        $expire = DateTimeHelper::currentUTCDateTime();
        $pastTime = $expire->sub($interval);

        $drafts = DB::table(Table::ELEMENTS, 'elements')
            ->select('elements.draftId', 'elements.type')
            ->join(new Alias(Table::DRAFTS, 'drafts'), 'drafts.id', '=', 'elements.draftId')
            ->where('drafts.saved', false)
            ->whereNull('drafts.canonicalId')
            ->where('elements.dateUpdated', '<', $pastTime)
            ->get();

        $elementsService = Craft::$app->getElements();

        foreach ($drafts as $draftInfo) {
            /** @var class-string<ElementInterface> $elementType */
            $elementType = $draftInfo->type;
            $draft = $elementType::find()
                ->draftId($draftInfo->draftId)
                ->status(null)
                ->site('*')
                ->one();

            if ($draft) {
                $elementsService->deleteElement($draft, true);
            } else {
                // Perhaps the draft's row in the `entries` table was deleted manually or something.
                // Just drop its row in the `drafts` table, and let that cascade to `elements` and whatever other tables
                // still have rows for the draft.
                DB::table(Table::DRAFTS)->delete($draftInfo->draftId);
            }

            Craft::info("Deleted unsaved draft {$draftInfo->draftId}", __METHOD__);
        }
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
        return DB::table(Table::DRAFTS)->insertGetId([
            'canonicalId' => $canonicalId,
            'creatorId' => $creatorId,
            'provisional' => $provisional,
            'name' => $name,
            'notes' => $notes,
            'trackChanges' => $trackChanges,
        ]);
    }
}
