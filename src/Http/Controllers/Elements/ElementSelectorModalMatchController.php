<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Elements;

use CraftCms\Cms\Element\ElementHelper;
use CraftCms\Cms\Element\ElementIndexes;
use CraftCms\Cms\Http\Requests\ElementIndexRequest;
use CraftCms\Cms\Support\Typecast;
use Illuminate\Http\JsonResponse;

/**
 * Tells an element selector modal whether an element meets its selection condition and criteria, such as an entry
 * created from the modal, before the modal chooses it.
 *
 * @since 6.0.0
 */
readonly class ElementSelectorModalMatchController
{
    public function __invoke(ElementIndexRequest $request): JsonResponse
    {
        $request->validate([
            'elementId' => ['required', 'integer'],
            'siteId' => ['nullable', 'integer'],
            'criteria' => ['nullable', 'array'],
        ]);

        $elementType = $request->elementType();
        $query = $elementType::find()->status(null);

        // What the modal lists, saved unpublished drafts included
        Typecast::configure($query, app(ElementIndexes::class)->baseCriteria($elementType));

        if ($criteria = $request->array('criteria')) {
            Typecast::configure($query, ElementHelper::cleanseQueryCriteria($criteria));
        }

        // A separate clause, rather than `id()`, so it doesn't replace an ID restriction from the criteria
        $query
            ->where('elements.id', $request->integer('elementId'))
            ->siteId($request->input('siteId'));

        $request->selectionCondition()?->modifyQuery($query);

        return new JsonResponse([
            'matches' => $query->exists(),
        ]);
    }
}
