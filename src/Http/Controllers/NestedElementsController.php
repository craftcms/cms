<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers;

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Elements;
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

    public function reorder(NestedElementsRequest $request): Response
    {
        $request->authorizeReorder();

        $this->elements->reorderNestedElements(
            $request->owner(),
            $request->nestedElements(),
            $request->elementIds(),
            $request->offset(),
        );

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
