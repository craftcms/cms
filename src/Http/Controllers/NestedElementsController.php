<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers;

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Contracts\NestedElementInterface;
use CraftCms\Cms\Element\ElementCaches;
use CraftCms\Cms\Element\Elements;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Http\Requests\NestedElementsRequest;
use CraftCms\Cms\Http\RespondsWithFlash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

use function CraftCms\Cms\t;

readonly class NestedElementsController
{
    use RespondsWithFlash;

    public function __construct(
        private Elements $elements,
    ) {}

    public function reorder(NestedElementsRequest $request, ElementCaches $elementCaches): Response
    {
        $nestedElements = $request->nestedElements();

        if ($nestedElements instanceof ElementQueryInterface) {
            $oldSortOrders = (clone $nestedElements)
                ->status(null)
                ->asArray()
                ->select(['id', 'sortOrder'])
                ->pluck('sortOrder', 'id')
                ->all();
        } else {
            $oldSortOrders = $nestedElements
                ->keyBy(fn (ElementInterface $element) => $element->id)
                /** @phpstan-ignore-next-line */
                ->map(fn (NestedElementInterface $element) => $element->getSortOrder())
                ->all();
        }

        // Build the full list of IDs in the new sort order
        $allIds = array_diff(array_keys($oldSortOrders), $request->elementIds());
        array_splice($allIds, $request->offset(), 0, $request->elementIds());

        // Update all the incorrect sort orders
        foreach ($allIds as $i => $id) {
            $sortOrder = $i + 1;
            if (! isset($oldSortOrders[$id]) || $sortOrder !== $oldSortOrders[$id]) {
                DB::table(Table::ELEMENTS_OWNERS)
                    ->where('ownerId', $request->owner()->id)
                    ->where('elementId', $id)
                    ->update([
                        'sortOrder' => $sortOrder,
                    ]);
            }
        }

        $elementCaches->invalidateForElement($request->owner());

        return $this->asSuccess(t('New {total, plural, =1{position} other{positions}} saved.', [
            'total' => count($request->elementIds()),
        ]));
    }

    public function destroy(NestedElementsRequest $request): Response
    {
        $element = $request->nestedElement();
        Gate::authorize('delete', $element);

        // If the element primarily belongs to a different element, just delete the ownership
        if ($element->getPrimaryOwnerId() !== $request->owner()->id) {
            DB::table(Table::ELEMENTS_OWNERS)
                ->where('ownerId', $request->owner()->id)
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
