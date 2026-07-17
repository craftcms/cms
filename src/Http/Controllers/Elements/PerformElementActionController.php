<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Elements;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\CurrentElementIndex;
use CraftCms\Cms\Element\ElementActions;
use CraftCms\Cms\Element\ElementIndexes;
use CraftCms\Cms\Element\ElementSources;
use CraftCms\Cms\Http\Requests\ElementIndexRequest;
use CraftCms\Cms\Http\Resources\ElementIndexResource;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Translation\I18N as TranslationI18N;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class PerformElementActionController
{
    use RespondsWithFlash;

    public function __construct(
        private readonly ElementActions $elementActions,
        private readonly ElementIndexes $elementIndexes,
        private readonly ElementSources $elementSources,
        private readonly TranslationI18N $i18N,
    ) {}

    public function __invoke(ElementIndexRequest $request, CurrentElementIndex $currentElementIndex): SymfonyResponse
    {
        $validated = $request->validate([
            'elementAction' => ['required', 'string'],
            'elementIds' => ['required', 'array'],
        ]);

        /** @var class-string<ElementInterface> $elementType */
        $elementType = $request->elementType();
        $actionClass = $validated['elementAction'];
        $elementIds = $validated['elementIds'];
        $context = $request->context();

        [$sourceKey, $source] = $this->elementIndexes->resolveSource($elementType, $request->input('source'), $context);
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

        $result = $this->elementActions->invoke(
            action: $action,
            query: (clone $elementQuery)
                ->offset(0)
                ->limit(null)
                ->reorder()
                ->positionedAfter(null)
                ->positionedBefore(null)
                ->id($elementIds)
                ->status(null)
        );

        abort_if(! $result['valid'], 400, 'Element action params did not validate');

        if ($action->isDownload()) {
            return $action->getResponse() ?? abort(500, 'Download element actions must provide a response');
        }

        if (! $result['success']) {
            return $this->asFailure($result['message']);
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

        return $this->asSuccess($result['message'], $responseData);
    }
}
