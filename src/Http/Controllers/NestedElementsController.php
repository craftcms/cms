<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers;

use CraftCms\Cms\Auth\SessionAuth;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Contracts\NestedElementInterface;
use CraftCms\Cms\Element\ElementCaches;
use CraftCms\Cms\Element\ElementCollection;
use CraftCms\Cms\Element\Elements;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Http\RespondsWithFlash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

use function CraftCms\Cms\t;

readonly class NestedElementsController
{
    use RespondsWithFlash;

    private ElementInterface $owner;

    private ElementQueryInterface|ElementCollection $nestedElements;

    public function __construct(
        private Request $request,
        private Elements $elements,
    ) {
        $this->request->validate([
            'ownerElementType' => ['required', 'string'],
            'ownerId' => ['required', 'integer'],
            'ownerSiteId' => ['required', 'integer'],
            'attribute' => ['required', 'string'],
        ]);

        // Get the owner element
        /** @var class-string<ElementInterface> $ownerElementType */
        $ownerElementType = $this->request->input('ownerElementType');
        $ownerId = $this->request->integer('ownerId');
        $ownerSiteId = $this->request->integer('ownerSiteId');
        $owner = $this->elements->getElementById($ownerId, $ownerElementType, $ownerSiteId);

        abort_if(is_null($owner), 400, 'Invalid owner params');

        $this->owner = $owner;

        // Make sure they're authorized to manage it
        $attribute = $this->request->input('attribute');
        if (
            ! SessionAuth::checkAuthorization(sprintf('manageNestedElements::%s::%s', $owner->id, $attribute)) &&
            (
                $owner->id === $owner->getCanonicalId() ||
                ! SessionAuth::checkAuthorization(sprintf('manageNestedElements::%s::%s', $owner->getCanonicalId(), $attribute))
            )
        ) {
            abort(403, 'User is not authorized to perform this action');
        }

        // Set the nested elements for the action
        $this->nestedElements = $this->owner->$attribute;
    }

    public function reorder(ElementCaches $elementCaches): Response
    {
        $this->request->validate([
            'elementIds' => ['required', 'array'],
            'offset' => ['required', 'integer'],
        ]);

        $ids = array_map(fn ($id) => (int) $id, $this->request->array('elementIds'));

        $offset = $this->request->integer('offset');

        if ($this->nestedElements instanceof ElementQueryInterface) {
            $oldSortOrders = (clone $this->nestedElements)
                ->status(null)
                ->asArray()
                ->select(['id', 'sortOrder'])
                ->pluck('sortOrder', 'id')
                ->all();
        } else {
            $oldSortOrders = $this->nestedElements
                ->keyBy(fn (ElementInterface $element) => $element->id)
                /** @phpstan-ignore-next-line */
                ->map(fn (NestedElementInterface $element) => $element->getSortOrder())
                ->all();
        }

        // Build the full list of IDs in the new sort order
        $allIds = array_diff(array_keys($oldSortOrders), $ids);
        array_splice($allIds, $offset, 0, $ids);

        // Update all the incorrect sort orders
        foreach ($allIds as $i => $id) {
            $sortOrder = $i + 1;
            if (! isset($oldSortOrders[$id]) || $sortOrder !== $oldSortOrders[$id]) {
                DB::table(Table::ELEMENTS_OWNERS)
                    ->where('ownerId', $this->owner->id)
                    ->where('elementId', $id)
                    ->update([
                        'sortOrder' => $sortOrder,
                    ]);
            }
        }

        $elementCaches->invalidateForElement($this->owner);

        return $this->asSuccess(t('New {total, plural, =1{position} other{positions}} saved.', [
            'total' => count($ids),
        ]));
    }

    public function destroy(): Response
    {
        $this->request->validate([
            'elementId' => ['required', 'integer'],
        ]);

        $elementId = $this->request->integer('elementId');

        if ($this->nestedElements instanceof ElementQueryInterface) {
            $element = $this->nestedElements
                ->id($elementId)
                ->status(null)
                ->drafts(null)
                ->provisionalDrafts(null)
                ->one();
        } else {
            $element = $this->nestedElements->first(
                fn (ElementInterface $element) => (
                    $element->id === $elementId ||
                    $element->getCanonicalId() === $elementId
                )
            );
        }

        abort_if(is_null($element), 400, 'Invalid elementId param');

        Gate::authorize('delete', $element);

        // If the element primarily belongs to a different element, just delete the ownership
        /** @var NestedElementInterface $element */
        if ($element->getPrimaryOwnerId() !== $this->owner->id) {
            DB::table(Table::ELEMENTS_OWNERS)
                ->where('ownerId', $this->owner->id)
                ->where('elementId', $element->id)
                ->delete();

            $success = true;
        } else {
            $success = $this->elements->deleteElement($element);
        }

        if (! $success) {
            return $this->asFailure(t('Couldn’t delete {type}.', [
                'type' => $element::lowerDisplayName(),
            ]));
        }

        return $this->asSuccess(t('{type} deleted.', [
            'type' => $element::displayName(),
        ]));
    }
}
