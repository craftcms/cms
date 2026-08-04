<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Elements;

use CraftCms\Cms\Cp\FormFields;
use CraftCms\Cms\Cp\Html\PreviewHtml;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Contracts\NestedElementInterface;
use CraftCms\Cms\Element\DeletionBlockers\Contracts\DeletionBlockerInterface;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Element\ElementCollection;
use CraftCms\Cms\Element\Elements;
use CraftCms\Cms\Element\Jobs\ReplaceReferences;
use CraftCms\Cms\Element\Jobs\ReplaceRelations;
use CraftCms\Cms\Element\Queries\Contracts\NestedElementQueryInterface;
use CraftCms\Cms\Element\Validation\Rules\ElementTypeRule;
use CraftCms\Cms\Field\FieldReferences;
use CraftCms\Cms\Http\Requests\ElementRequest;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Http\Responses\CpModalResponse;
use CraftCms\Cms\Support\Facades\HtmlStack;
use CraftCms\Cms\Support\Html;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

use function CraftCms\Cms\t;

readonly class DeleteElementsController
{
    use RespondsWithFlash;

    /** @var class-string<ElementInterface> */
    protected string $elementType;

    protected bool $hardDelete;

    /** @var ElementCollection<array-key, ElementInterface> */
    protected ElementCollection $elements;

    public function __construct(
        protected ElementRequest $request,
    ) {
        $this->elementType = $this->request->elementType();
        $this->hardDelete = $this->request->boolean('hardDelete');
        $this->elements = $this->elements();
    }

    public function deletionBlockers(PreviewHtml $previewHtml): JsonResponse
    {
        $elements = $this->elements->when(
            // filter out elements that primarily belong to a different element,
            // as they won't actually be getting deleted
            is_subclass_of($this->elementType, NestedElementInterface::class),
            /** @phpstan-ignore-next-line */
            fn (ElementCollection $elements) => $elements->filter(fn (NestedElementInterface $element) => $this->elementOwnedByPrimaryOwner($element)),
        );

        $blockers = collect($this->elementType::deletionBlockers($elements, $this->hardDelete))
            ->filter(fn (DeletionBlockerInterface $blocker) => $blocker->isActive())
            ->map(fn (DeletionBlockerInterface $blocker) => [
                'summary' => $blocker->getSummary(),
                'details' => $blocker->getDetails(),
                'actions' => $blocker->getActions(),
            ])
            ->values()
            ->all();

        $elementPreview = $previewHtml->elementPreviewHtml(
            elements: $this->elements->all(),
            showStatus: false,
        );

        return new JsonResponse([
            'blockers' => $blockers,
            'elementPreview' => $elementPreview,
            'totalElements' => $elements->count(),
            'headHtml' => HtmlStack::headHtml(),
            'bodyHtml' => HtmlStack::bodyHtml(),
        ]);
    }

    public function destroy(Elements $elementsService): JsonResponse
    {
        $deleteOwnership = [];
        $elementsToDelete = 0;
        $failureCount = 0;

        foreach ($this->elements as $element) {
            if (
                $element instanceof NestedElementInterface &&
                ! $this->elementOwnedByPrimaryOwner($element)
            ) {
                $deleteOwnership[$element->getOwnerId()][] = $element->id;

                continue;
            }

            $elementsToDelete++;
            if (! $elementsService->deleteElement($element, $this->hardDelete)) {
                $failureCount++;
            }
        }

        foreach ($deleteOwnership as $ownerId => $elementIds) {
            DB::table(Table::ELEMENTS_OWNERS)
                ->whereIn('elementId', $elementIds)
                ->where('ownerId', $ownerId)
                ->delete();
        }

        $showAsFailure = $failureCount !== 0 && $failureCount === $elementsToDelete;

        if ($showAsFailure) {
            $message = t('Couldn’t delete {type}.', [
                'type' => $elementsToDelete === 1 ? $this->elementType::lowerDisplayName() : $this->elementType::pluralLowerDisplayName(),
            ]);
        } else {
            $message = t('{type} deleted.', [
                'type' => $elementsToDelete === 1 ? $this->elementType::displayName() : $this->elementType::pluralDisplayName(),
            ]);
        }

        return new JsonResponse([
            'message' => $message,
            'showAsFailure' => $showAsFailure,
        ]);
    }

    public function replaceRelationsModal(): CpModalResponse
    {
        $this->request->validate([
            'sourceElementType' => ['required', 'string', new ElementTypeRule],
        ]);

        /** @var class-string<ElementInterface> $sourceElementType */
        $sourceElementType = $this->request->input('sourceElementType');
        $targetElementIds = $this->elements->ids();

        return new CpModalResponse()
            ->action('delete-elements/replace-relations')
            ->contentHtml(fn () => FormFields::elementSelectFieldHtml([
                'label' => t('Choose a new {type}', [
                    'type' => $this->elementType::lowerDisplayName(),
                ]),
                'name' => 'newTargetId',
                'elementType' => $this->elementType,
                'criteria' => [
                    'id' => $targetElementIds->map(fn (int $id) => "not $id")->all(),
                ],
                'single' => true,
            ]).
                Html::hiddenInput('elementType', $this->elementType).
                $targetElementIds->map(fn (int $id) => (string) Html::hiddenInput('elementIds[]', (string) $id))->join('').
                Html::hiddenInput('hardDelete', $this->hardDelete ? '1' : '0').
                Html::hiddenInput('sourceElementType', $sourceElementType)
            )
            ->submitButtonLabel(t('Replace'));
    }

    public function replaceRelations(): Response
    {
        $this->request->validate([
            'sourceElementType' => ['required', 'string', new ElementTypeRule],
            'newTargetId' => ['required', 'integer'],
        ]);

        /** @var class-string<ElementInterface> $sourceElementType */
        $sourceElementType = $this->request->input('sourceElementType');
        $newTargetId = $this->request->integer('newTargetId');

        if (! $newTargetId) {
            return $this->asFailure(t('No new {type} selected.', [
                'type' => $this->elementType::lowerDisplayName(),
            ]));
        }

        $oldTargetIds = $this->elements->ids()->all();
        $sourceIds = $sourceElementType::find()
            ->siteId('*')
            ->unique()
            ->relatedTo(['targetElement' => $oldTargetIds])
            ->status(null)
            ->drafts(null)
            ->withProvisionalDrafts()
            ->revisions(null)
            ->ids();

        dispatch(new ReplaceRelations(
            sourceElementType: $sourceElementType,
            targetElementType: $this->elementType,
            sourceIds: $sourceIds,
            oldTargetIds: $oldTargetIds,
            newTargetId: $newTargetId,
        ));

        return $this->asSuccess(t('{numRelations, plural, =1{Relation} other{Relations}} queued to be replaced.', [
            'numRelations' => count($sourceIds),
        ]));
    }

    public function replaceReferencesModal(): CpModalResponse
    {
        $targetElementIds = $this->elements->ids();

        return new CpModalResponse()
            ->action('delete-elements/replace-references')
            ->contentHtml(fn () => FormFields::elementSelectFieldHtml([
                'label' => t('Choose a new {type}', [
                    'type' => $this->elementType::lowerDisplayName(),
                ]),
                'name' => 'newTargetId',
                'elementType' => $this->elementType,
                'criteria' => [
                    'id' => $targetElementIds->map(fn (int $id) => "not $id")->all(),
                ],
                'single' => true,
            ]).
                Html::hiddenInput('elementType', $this->elementType).
                $targetElementIds->map(fn (int $id) => (string) Html::hiddenInput('elementIds[]', (string) $id))->join('').
                Html::hiddenInput('hardDelete', $this->hardDelete ? '1' : '0')
            )
            ->submitButtonLabel(t('Replace'));
    }

    public function replaceReferences(FieldReferences $fieldReferences): Response
    {
        $this->request->validate([
            'newTargetId' => ['required', 'integer'],
        ]);

        $newTargetId = $this->request->integer('newTargetId');

        if (! $newTargetId) {
            return $this->asFailure(t('No new {type} selected.', [
                'type' => $this->elementType::lowerDisplayName(),
            ]));
        }

        $newTarget = $this->elementType::find()
            ->id($newTargetId)
            ->siteId('*')
            ->unique()
            ->drafts(false)
            ->revisions(false)
            ->trashed(false)
            ->status(null)
            ->one();

        if (! $newTarget) {
            return $this->asFailure(t('The selected {type} could not be found.', [
                'type' => $this->elementType::lowerDisplayName(),
            ]));
        }

        $oldTargetIds = $this->elements->ids()->all();
        $refCount = $fieldReferences->referenceCountForTargets($oldTargetIds);

        foreach ($fieldReferences->replacementGroupsForTargets($oldTargetIds) as $sourceElementType => $typeRefs) {
            foreach ($typeRefs as $sourceSiteId => $siteRefs) {
                $refs = [];

                foreach ($siteRefs as $ref) {
                    $refs[] = [
                        'fieldInstanceUid' => $ref->fieldInstanceUid,
                        'sourceId' => (int) $ref->sourceId,
                    ];
                }

                dispatch(new ReplaceReferences(
                    sourceElementType: $sourceElementType,
                    targetElementType: $this->elementType,
                    sourceSiteId: $sourceSiteId === '*' ? null : (int) $sourceSiteId,
                    refs: $refs,
                    oldTargetIds: $oldTargetIds,
                    newTargetId: $newTargetId,
                ));
            }
        }

        return $this->asSuccess(t('{numReferences, plural, =1{Reference} other{References}} queued to be replaced.', [
            'numReferences' => $refCount,
        ]));
    }

    /** @return ElementCollection<array-key, ElementInterface> */
    private function elements(): ElementCollection
    {
        $this->request->validate([
            'elementIds' => ['required', 'array'],
            'elementIds.*' => ['integer'],
            'siteId' => ['nullable'],
        ]);

        $elementIds = array_map(fn ($id) => (int) $id, $this->request->array('elementIds'));
        $siteId = $this->request->input('siteId');

        $query = $this->elementType::find()
            ->id($elementIds)
            ->siteId($siteId ?? '*')
            ->unique()
            ->status(null)
            ->drafts(null)
            ->savedDraftsOnly(false);

        $withDescendants = ! $this->hardDelete && $this->request->boolean('withDescendants');

        if ($withDescendants) {
            $query
                ->with([
                    [
                        'descendants',
                        [
                            'orderBy' => [Table::STRUCTUREELEMENTS.'.lft' => SORT_DESC],
                            'status' => null,
                            'withStructure' => true,
                        ],
                    ],
                ])
                ->withStructure()
                ->orderByDesc(Table::STRUCTUREELEMENTS.'.lft');
        }

        if ($query instanceof NestedElementQueryInterface) {
            $ownerId = $this->request->input('ownerId');
            $query->ownerId($ownerId);
        }

        $elements = [];
        $elementIds = [];

        foreach ($query->all() as $element) {
            if (! $element instanceof ElementInterface) {
                continue;
            }

            if (isset($elementIds[$element->id])) {
                continue;
            }

            if (! Gate::check('view', $element)) {
                continue;
            }

            if (! Gate::check('delete', $element)) {
                continue;
            }

            $elements[] = $element;
            $elementIds[$element->id] = true;

            if ($withDescendants) {
                foreach ($element->getDescendants()->all() as $descendant) {
                    if (! $descendant instanceof ElementInterface) {
                        continue;
                    }

                    if (isset($elementIds[$descendant->id])) {
                        continue;
                    }

                    if (! Gate::check('view', $descendant)) {
                        continue;
                    }

                    if (! Gate::check('delete', $descendant)) {
                        continue;
                    }

                    $elements[] = $descendant;
                    $elementIds[$descendant->id] = true;
                }
            }
        }

        return ElementCollection::make($elements);
    }

    private function elementOwnedByPrimaryOwner(NestedElementInterface $element): bool
    {
        $ownerId = $element->getOwnerId();

        return ! $ownerId || $element->getPrimaryOwnerId() === $ownerId;
    }
}
