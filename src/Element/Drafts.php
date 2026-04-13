<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element;

use CraftCms\Cms\Cms;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Contracts\NestedElementInterface;
use CraftCms\Cms\Element\Events\ApplyingDraft;
use CraftCms\Cms\Element\Events\CreatingDraft;
use CraftCms\Cms\Element\Events\DraftApplied;
use CraftCms\Cms\Element\Events\DraftCreated;
use CraftCms\Cms\Element\Exceptions\InvalidElementException;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\Structures;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Throwable;
use Tpetry\QueryExpressions\Language\Alias;
use yii\base\Exception;

use function CraftCms\Cms\t;

#[Singleton]
readonly class Drafts
{
    public const string CONTEXT_PREVIEW_USER_ID = 'craft.preview-user-id';

    public function __construct(
        private Elements $elements,
    ) {}

    /**
     * Returns drafts for a given element ID that the current user is allowed to edit
     *
     * @return Collection<ElementInterface>
     */
    public function getEditableDrafts(ElementInterface $element, ?string $permission = null): Collection
    {
        $user = Auth::user();

        if (! $user) {
            return collect();
        }

        /** @var ElementQueryInterface $query */
        $query = $element::find()
            ->draftOf($element)
            ->siteId($element->siteId)
            ->status(null)
            ->orderBy(['dateUpdated' => SORT_DESC]);

        if (! $permission || ! $user->can($permission)) {
            $query->draftCreator($user->id);
        }

        return collect($query->all());
    }

    /**
     * Creates a new draft for the given element.
     *
     * @template T of ElementInterface
     *
     * @param  T  $canonical  The element to create a draft for
     * @param  int|null  $creatorId  The user ID that the draft should be attributed to
     * @param  string|null  $name  The draft name
     * @param  string|null  $notes  The draft notes
     * @param  array  $newAttributes  any attributes to apply to the draft
     * @param  bool  $provisional  Whether to create a provisional draft
     * @return T The new draft
     *
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

        event($event = new CreatingDraft(
            canonical: $canonical,
            creatorId: $creatorId,
            provisional: $provisional,
            draftName: $name,
            draftNotes: $notes,
        ));

        $name = $event->draftName;
        $notes = $event->draftNotes;

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
            $newAttributes['draftCreatorId'] = $creatorId;
            $newAttributes['draftName'] = $name;
            $newAttributes['draftNotes'] = $notes;
            $newAttributes['trackDraftChanges'] = $canonical::trackChanges();
            $newAttributes['markDraftAsSaved'] = $markAsSaved;

            $draft = $this->elements->duplicateElement($canonical, $newAttributes);

            DB::table(Table::ELEMENTS_OWNERS)->insertUsing(
                columns: ['elementId', 'ownerId', 'sortOrder'],
                query: DB::table(Table::ELEMENTS_OWNERS, 'o')
                    ->select('o.elementId', DB::raw($draft->id), 'o.sortOrder')
                    ->where('o.ownerId', $canonical->id)
                    ->whereNotExists(function ($query) use ($draft) {
                        $query->selectRaw('1')
                            ->from(Table::ELEMENTS_OWNERS)
                            ->whereColumn('elementId', 'o.elementId')
                            ->where('ownerId', $draft->id);
                    }),
            );

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        event(new DraftCreated(
            canonical: $canonical,
            creatorId: $creatorId,
            provisional: $provisional,
            draftName: $name,
            draftNotes: $notes,
            draft: $draft,
        ));

        return $draft;
    }

    /**
     * Returns the next auto-generated draft name that should be assigned, for the given canonical element.
     *
     * @param  int  $canonicalId  The canonical element’s ID
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
     * @throws Throwable
     */
    public function saveElementAsDraft(ElementInterface $element, ?int $creatorId = null, ?string $name = null, ?string $notes = null, bool $markAsSaved = true): bool
    {
        $name ??= t('First draft');

        // Create the draft row
        $draftId = $this->insertDraftRow($name, $notes, $creatorId);

        $element->draftId = $draftId;
        $element->draftCreatorId = $creatorId;
        $element->draftName = $name;
        $element->draftNotes = $notes;
        $element->markDraftAsSaved = $markAsSaved;

        // Try to save and return the result
        return $this->elements->saveElement($element);
    }

    /**
     * Applies a draft to its canonical element, and deletes the draft.
     *
     * If an unpublished draft is passed, its draft data will simply be removed from it.
     *
     * @template T of ElementInterface
     *
     * @param  T  $draft  The draft
     * @param  array  $newAttributes  Any attributes to apply to the canonical element
     * @return T The canonical element with the draft applied to it
     *
     * @throws Throwable
     */
    public function applyDraft(ElementInterface $draft, array $newAttributes = []): ElementInterface
    {
        $canonical = $draft->getCanonical(true);
        $originalDraft = $draft;

        // If the canonical element ended up being from a different site than the draft, get the draft in that site
        if ($canonical->siteId !== $draft->siteId) {
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

        event(new ApplyingDraft(
            canonical: $canonical,
            creatorId: $draft->draftCreatorId,
            draftName: $draft->draftName,
            draftNotes: $draft->draftNotes,
            draft: $draft,
        ));

        $draftNotes = $draft->draftNotes;

        DB::beginTransaction();
        try {
            if ($canonical !== $draft) {
                // Merge in any attribute & field values that were updated in the canonical element, but not the draft
                if ($draft::trackChanges() && ElementHelper::isOutdated($draft)) {
                    $this->elements->mergeCanonicalChanges($draft);
                }

                // "Duplicate" the draft with the canonical element’s ID and UID
                $newCanonical = $this->elements->updateCanonicalElement($draft, array_merge($newAttributes, [
                    'revisionNotes' => $draftNotes ?: t('Applied “{name}”', ['name' => $draft->draftName]),
                ]));

                // Move the new canonical element after the draft?
                if ($draft->structureId && $draft->root) {
                    Structures::moveAfter($draft->structureId, $newCanonical, $draft);
                }

                // Now delete the draft
                $this->elements->deleteElement($draft, true);
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
                $draft->errors()->merge($e->element->errors()->getMessages());
            }

            throw $e;
        }

        event(new DraftApplied(
            canonical: $newCanonical,
            creatorId: $draft->draftCreatorId,
            draftName: $draft->draftName,
            draftNotes: $draft->draftNotes,
            draft: $draft,
        ));

        // if we were on another site when the applyDraft was triggered,
        // ensure we return the canonical element for the site we were on
        if ($newCanonical->siteId !== $originalDraft->siteId) {
            return $originalDraft->getCanonical();
        }

        return $newCanonical;
    }

    /**
     * Removes draft data from the given draft.
     *
     * @throws InvalidElementException
     */
    public function removeDraftData(ElementInterface $draft): void
    {
        $draftId = $draft->draftId;

        $draft->draftId = null;
        $draft->firstSave = true;

        // We still need to validate so the SlugValidator gets run
        $draft->setScenario(Element::SCENARIO_ESSENTIALS);
        $draft->validate();

        // If there are any errors on the URI, re-validate as disabled
        if ($draft->errors()->has('uri') && $draft->enabled) {
            $draft->enabled = false;
            $draft->validate();
        }

        try {
            // no need to propagate or save content here – and it could end up overriding any
            // content changes made to other sites from a previous onAfterPropagate(), etc.
            if ($draft->errors()->isNotEmpty() || ! $this->elements->saveElement($draft, false, false)) {
                throw new InvalidElementException($draft, "Draft $draft->id could not be applied because it doesn't validate.");
            }

            DB::table(Table::DRAFTS)->delete($draftId);
        } catch (Throwable $e) {
            // Put everything back
            $draft->draftId = $draftId;
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
        if (Cms::config()->purgeUnsavedDraftsDuration === 0) {
            return;
        }

        $drafts = DB::table(Table::ELEMENTS, 'elements')
            ->select('elements.draftId', 'elements.type')
            ->join(new Alias(Table::DRAFTS, 'drafts'), 'drafts.id', '=', 'elements.draftId')
            ->where('drafts.saved', false)
            ->whereNull('drafts.canonicalId')
            ->where('elements.dateUpdated', '<', now()->subSeconds(Cms::config()->purgeUnsavedDraftsDuration))
            ->get();

        foreach ($drafts as $draftInfo) {
            /** @var class-string<ElementInterface> $elementType */
            $elementType = $draftInfo->type;
            $draft = $elementType::find()
                ->draftId($draftInfo->draftId)
                ->status(null)
                ->site('*')
                ->one();

            if ($draft) {
                $this->elements->deleteElement($draft, true);
            } else {
                // Perhaps the draft's row in the `entries` table was deleted manually or something.
                // Just drop its row in the `drafts` table, and let that cascade to `elements` and whatever other tables
                // still have rows for the draft.
                DB::table(Table::DRAFTS)->delete($draftInfo->draftId);
            }

            Log::info("Deleted unsaved draft {$draftInfo->draftId}", [__METHOD__]);
        }
    }

    /**
     * Creates a new row in the `drafts` table.
     *
     * @return int The new draft ID
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

    /**
     * @template T of ElementInterface
     *
     * @param  T[]  $elements
     * @return T[]
     */
    public function withProvisionalDrafts(array $elements, ?User $user = null): array
    {
        $drafts = $this->provisionalDrafts($elements, $user);

        if (empty($drafts)) {
            return $elements;
        }

        $elementsWithDrafts = $elements;

        foreach ($elements as $index => $element) {
            if (! isset($drafts[$element->id])) {
                continue;
            }

            $draft = $drafts[$element->id];
            $draft->setCanonical($element);

            if ($element->structureId !== null) {
                $draft->structureId = $element->structureId;
                $draft->root = $element->root;
                $draft->lft = $element->lft;
                $draft->rgt = $element->rgt;
                $draft->level = $element->level;
            }

            if ($element instanceof NestedElementInterface && $draft instanceof NestedElementInterface) {
                $draft->setOwnerId($element->getOwnerId());
            }

            $elementsWithDrafts[$index] = $draft;
        }

        return $elementsWithDrafts;
    }

    /**
     * @template T of ElementInterface
     *
     * @param  T[]  $elements
     */
    public function loadProvisionalChanges(array $elements, ?User $user = null): void
    {
        $drafts = $this->provisionalDrafts($elements, $user);

        if (empty($drafts)) {
            return;
        }

        foreach ($elements as $element) {
            if (! isset($drafts[$element->id])) {
                continue;
            }

            $draft = $drafts[$element->id];
            $element->hasProvisionalChanges = true;

            foreach ($draft->getModifiedAttributes() as $name) {
                if ($element->canSetProperty($name)) {
                    $element->$name = $draft->$name;
                }
            }

            foreach ($draft->getModifiedFields() as $handle) {
                $element->setFieldValue($handle, $draft->getFieldValue($handle));
            }
        }
    }

    /**
     * @param  ElementInterface[]  $elements
     * @return ElementInterface[]
     */
    private function provisionalDrafts(array $elements, ?User $user = null): array
    {
        if ($user === null && Context::hasHidden(self::CONTEXT_PREVIEW_USER_ID)) {
            $userId = Context::getHidden(self::CONTEXT_PREVIEW_USER_ID);
            $user = User::find()->id($userId)->status(null)->one();
        }

        $user ??= Auth::user();

        if (! $user) {
            return [];
        }

        $canonicalElements = array_filter(
            $elements,
            fn (ElementInterface $element) => ! $element->getIsDraft() && ! $element->getIsRevision(),
        );

        if (empty($canonicalElements)) {
            return [];
        }

        $first = reset($canonicalElements);

        return $first::find()
            ->draftOf($canonicalElements)
            ->draftCreator($user)
            ->provisionalDrafts()
            ->siteId($first->siteId)
            ->status(null)
            ->indexBy('canonicalId')
            ->all();
    }
}
