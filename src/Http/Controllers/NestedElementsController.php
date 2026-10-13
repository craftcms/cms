<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers;

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Drafts;
use CraftCms\Cms\Element\Elements;
use CraftCms\Cms\Http\Requests\NestedElementsRequest;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Workflow\Workflows;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

use function CraftCms\Cms\t;

/**
 * @since 6.0.0
 */
readonly class NestedElementsController
{
    use RespondsWithFlash;

    public function __construct(
        private Elements $elements,
        private Drafts $drafts,
        private Workflows $workflows,
    ) {}

    public function reorder(NestedElementsRequest $request): Response
    {
        $request->authorizeReorder();
        Gate::authorize('save', $request->owner());

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
        $owner = $request->owner();
        Gate::authorize('delete', $element);

        // If the element primarily belongs to a different element, just delete the ownership
        if ($element->getPrimaryOwnerId() !== $owner->id) {
            $draftIds = $this->drafts->getDraftIdsForElement($owner);
            $this->workflows->withContentChangeLock($draftIds, function () use ($element, $owner): void {
                DB::table(Table::ELEMENTS_OWNERS)
                    ->where('ownerId', $owner->id)
                    ->where('elementId', $element->id)
                    ->delete();

                $this->elements->touchElementAndOwners($owner);
            });

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
