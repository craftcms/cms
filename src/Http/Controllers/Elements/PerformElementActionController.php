<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Elements;

use CraftCms\Cms\Element\Actions\Duplicate;
use CraftCms\Cms\Element\Contracts\ElementActionInterface;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Contracts\NestedElementInterface;
use CraftCms\Cms\Element\CurrentElementIndex;
use CraftCms\Cms\Element\ElementActions;
use CraftCms\Cms\Element\ElementIndexes;
use CraftCms\Cms\Element\Elements;
use CraftCms\Cms\Element\ElementSources;
use CraftCms\Cms\Element\NestedElementManager;
use CraftCms\Cms\Http\EmbeddedNestedElementScope;
use CraftCms\Cms\Http\Requests\ElementIndexRequest;
use CraftCms\Cms\Http\Resources\ElementIndexResource;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Translation\I18N as TranslationI18N;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * @since 6.0.0
 */
class PerformElementActionController
{
    use RespondsWithFlash;

    public function __construct(
        private readonly ElementActions $elementActions,
        private readonly Elements $elements,
        private readonly ElementIndexes $elementIndexes,
        private readonly ElementSources $elementSources,
        private readonly TranslationI18N $i18N,
    ) {}

    public function __invoke(
        ElementIndexRequest $request,
        CurrentElementIndex $currentElementIndex,
    ): SymfonyResponse {
        $validated = $request->validate([
            'elementAction' => ['required', 'string'],
            'elementIds' => ['required', 'array'],
        ]);

        /** @var class-string<ElementInterface> $elementType */
        $elementType = $request->elementType();
        $actionClass = $validated['elementAction'];
        $elementIds = $validated['elementIds'];
        $context = $request->context();
        $embedded = $context === ElementSources::CONTEXT_EMBEDDED_INDEX;

        if ($embedded) {
            $nestedElementScope = new EmbeddedNestedElementScope($request);
            $nestedSource = $nestedElementScope->indexSource($elementType);
            [$sourceKey, $source] = [$nestedSource::NESTED_KEY, $nestedSource->source];
            $owner = $nestedElementScope->owner();
            $elementQuery = $this->elementIndexes->buildQueryState(
                elementType: $elementType,
                source: $source,
                baseCriteria: $nestedSource->criteria,
            )['query'];
        } else {
            [$sourceKey, $source] = $this->elementIndexes->resolveSource(
                $elementType,
                $request->input('source'),
                $context,
            );
            $queryState = $this->elementIndexes->buildQueryState(
                elementType: $elementType,
                source: $source,
                condition: $request->condition(),
                baseCriteria: $request->baseCriteria(),
                criteria: $request->criteria(),
                filterConditionConfig: $request->filterConditionConfig(),
                collapsedElementIds: $request->collapsedElementIds(),
            );
            $elementQuery = $queryState['query'];
        }

        $currentElementIndex->activate($elementQuery);

        $actions = null;

        if ($request->isAdministrative($context) && isset($sourceKey)) {
            $actions = $this->elementActions->availableActions($elementType, $sourceKey, $elementQuery);
        }

        $action = $this->elementActions->resolveAction($actions ?? [], $actionClass);
        abort_if($action === null, 400, 'Element action is not supported by the element type');

        foreach ($action->settingsAttributes() as $paramName) {
            $paramValue = $request->input($paramName);

            if ($paramValue !== null) {
                $action->$paramName = $paramValue;
            }
        }

        if ($embedded) {
            $selectedElements = $nestedElementScope->selectedElements($elementIds, ! $action->isDownload());
        }

        $actionQuery = (clone $elementQuery)
            ->offset(0)
            ->limit(null)
            ->reorder()
            ->positionedAfter(null)
            ->positionedBefore(null)
            ->id($elementIds)
            ->status(null);

        if ($embedded && ! $action->isDownload()) {
            DB::beginTransaction();

            try {
                $positionsByElementId = [];

                if ($action instanceof Duplicate) {
                    $action->setNestedOwner($owner);
                    $preparedElements = $selectedElements;

                    // Duplicates go right after their sources, for nested elements that have an order.
                    if ($nestedElementScope->canReorder()) {
                        $orderedElementIds = (clone $elementQuery)
                            ->offset(0)
                            ->limit(null)
                            ->orderBy('sortOrder')
                            ->status(null)
                            ->ids();
                        $positionsByElementId = array_flip(array_map(intval(...), $orderedElementIds));
                    }
                } else {
                    $preparedElements = $selectedElements
                        ->map(fn (NestedElementInterface $element): NestedElementInterface => NestedElementManager::prepareElementForOwner($element, $owner));
                }
                $elementIds = $preparedElements->pluck('id')->all();

                $result = $this->elementActions->invoke(
                    action: $action,
                    query: $actionQuery->id($elementIds),
                );

                if ($result['success'] && $action instanceof Duplicate) {
                    $duplicateIds = $action->duplicateIdsBySourceId();

                    $preparedElements
                        ->sortByDesc(fn (NestedElementInterface $element): int => $positionsByElementId[$element->id] ?? -1)
                        ->each(function (NestedElementInterface $element) use ($duplicateIds, $owner, $elementQuery, $positionsByElementId): void {
                            $duplicateId = $duplicateIds[$element->id] ?? null;
                            $position = $positionsByElementId[$element->id] ?? null;

                            if ($duplicateId === null || $position === null) {
                                return;
                            }

                            $this->elements->reorderNestedElements(
                                $owner,
                                $elementQuery,
                                [$duplicateId],
                                $position + 1,
                            );
                        });
                }

                if (! $result['valid'] || ! $result['success']) {
                    DB::rollBack();
                } else {
                    DB::commit();
                }
            } catch (\Throwable $exception) {
                DB::rollBack();

                throw $exception;
            }
        } else {
            $result = $this->elementActions->invoke($action, $actionQuery);
        }

        abort_if(! $result['valid'], 400, 'Element action params did not validate');

        if ($action->isDownload()) {
            return $action->getResponse() ?? abort(500, 'Download element actions must provide a response');
        }

        if (! $result['success']) {
            return $this->asFailure($result['message']);
        }

        if ($embedded) {
            return $this->asSuccess($result['message']);
        }

        $responseData = new ElementIndexResource()->toArray($request);

        $formatter = $this->i18N->getFormatter();

        foreach ($this->elementSources->getSources($elementType, $context) as $source) {
            if (! isset($source['key'])) {
                continue;
            }

            $responseData['badgeCounts'][$source['key']] = isset($source['badgeCount'])
                ? $formatter->asDecimal($source['badgeCount'], 0)
                : null;
        }

        return $this->asSuccess($result['message'], $responseData, $this->actionRedirect($action));
    }

    /**
     * Where an action that sends the user elsewhere set its response to
     * redirect to.
     */
    private function actionRedirect(ElementActionInterface $action): ?string
    {
        $response = $action->getResponse();

        return $response?->isRedirect() ? $response->headers->get('Location') : null;
    }
}
