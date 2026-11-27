<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Elements;

use CraftCms\Cms\Element\ElementIndexes;
use CraftCms\Cms\Element\ElementIndexState;
use CraftCms\Cms\Element\ElementSources;
use CraftCms\Cms\Http\Requests\ElementIndexRequest;
use Illuminate\Http\JsonResponse;

/**
 * Tells an element selector modal whether an element meets its selection condition and criteria, such as an entry
 * created from the modal, before the modal chooses it.
 *
 * The element matches when one of the modal's sources lists it, as `PasteableElementsController` decides it.
 *
 * @since 6.0.0
 */
readonly class ElementSelectorModalMatchController
{
    public function __invoke(
        ElementIndexRequest $request,
        ElementIndexes $elementIndexes,
        ElementIndexState $elementIndexState,
    ): JsonResponse {
        $request->validate([
            'elementId' => ['required', 'integer'],
            'siteId' => ['nullable', 'integer'],
            'criteria' => ['nullable', 'array'],
            'sources' => ['nullable', 'array'],
            'sources.*' => ['string'],
        ]);

        $elementType = $request->elementType();
        $allowedSources = $request->input('sources');
        $sources = $elementIndexState
            ->sources($elementType, $request->context(), restrictTo: is_array($allowedSources) ? array_values($allowedSources) : null)
            ->filter(fn (array $source): bool => $source['type'] !== ElementSources::TYPE_HEADING && isset($source['key']));
        $selectionCondition = $request->selectionCondition();

        foreach ($sources as $source) {
            $query = $elementIndexes->buildQueryState(
                elementType: $elementType,
                source: $source,
                // What the modal lists, saved unpublished drafts included
                baseCriteria: $elementIndexes->baseCriteria($elementType),
                criteria: $request->criteria(),
            )['query'];

            $selectionCondition?->modifyQuery($query);

            // A separate clause, rather than `id()`, so it doesn't replace an ID restriction from the criteria
            $matches = $query
                ->where('elements.id', $request->integer('elementId'))
                ->siteId($request->input('siteId'))
                ->status($source['criteria']['status'] ?? null)
                ->exists();

            if ($matches) {
                return new JsonResponse(['matches' => true]);
            }
        }

        return new JsonResponse(['matches' => false]);
    }
}
