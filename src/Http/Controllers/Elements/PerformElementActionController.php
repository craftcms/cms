<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Elements;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\CurrentElementIndex;
use CraftCms\Cms\Element\ElementActions;
use CraftCms\Cms\Element\ElementExporters;
use CraftCms\Cms\Element\ElementSources;
use CraftCms\Cms\Http\Controllers\Elements\Concerns\InteractsWithElementIndexes;
use CraftCms\Cms\Http\Requests\ElementRequest;
use CraftCms\Cms\Http\Resources\ElementIndexResource;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Translation\I18N as TranslationI18N;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

readonly class PerformElementActionController
{
    use InteractsWithElementIndexes;
    use RespondsWithFlash;

    public function __construct(
        private ElementRequest $request,
        private ElementActions $elementActions,
        private ElementSources $elementSources,
        private TranslationI18N $i18N,
        private ElementExporters $elementExporters,
    ) {}

    public function __invoke(CurrentElementIndex $currentElementIndex): SymfonyResponse
    {
        $validated = $this->request->validate([
            'elementAction' => ['required', 'string'],
            'elementIds' => ['required', 'array'],
        ]);

        /** @var class-string<ElementInterface> $elementType */
        $elementType = $this->request->elementType();
        $actionClass = $validated['elementAction'];
        $elementIds = $validated['elementIds'];
        $context = $this->request->context();

        [$sourceKey, $source] = $this->resolveSource($elementType, $this->request->input('source'), $context);
        $fieldLayouts = $this->resolveFieldLayouts();
        $condition = $this->resolveElementIndexCondition();
        $viewState = $this->resolveViewState();
        $queryState = $this->buildElementQueryState($elementType, $source, $condition);
        $elementQuery = $queryState['query'];

        $currentElementIndex->activate($elementQuery);

        $actions = null;
        $exporters = null;

        if ($this->request->isAdministrative($context) && isset($sourceKey)) {
            $actions = $this->elementActions->availableActions($elementType, $sourceKey, $elementQuery);
            $exporters = $this->availableExporters($elementType, $sourceKey);
        }

        $action = $this->elementActions->resolveAction($actions ?? [], $actionClass);
        abort_if($action === null, 400, 'Element action is not supported by the element type');

        foreach ($action->settingsAttributes() as $paramName) {
            $paramValue = $this->request->input($paramName);

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
        );

        abort_if(! $result['valid'], 400, 'Element action params did not validate');

        if ($action->isDownload()) {
            return $action->getResponse() ?? abort(500, 'Download element actions must provide a response');
        }

        if (! $result['success']) {
            return $this->asFailure($result['message']);
        }

        $responseData = new ElementIndexResource(
            elementType: $elementType,
            elementQuery: $elementQuery,
            viewState: $viewState,
            fieldLayouts: $fieldLayouts,
            sourceKey: $sourceKey,
            context: $context,
            actions: $actions,
            exporters: $exporters,
            includeContainer: true,
            includeActions: true,
        )->toArray($this->request);

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
