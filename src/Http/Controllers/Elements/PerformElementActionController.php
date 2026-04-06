<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Elements;

use Closure;
use craft\base\ElementInterface;
use CraftCms\Cms\Element\Contracts\ElementExporterInterface;
use CraftCms\Cms\Element\ElementActions;
use CraftCms\Cms\Element\ElementExporters;
use CraftCms\Cms\Element\ElementSources;
use CraftCms\Cms\Element\Exceptions\InvalidTypeException;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Http\Controllers\Elements\Concerns\InteractsWithElementIndexes;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Support\Facades\HtmlStack;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Translation\I18N as TranslationI18N;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

use function CraftCms\Cms\t;

readonly class PerformElementActionController
{
    use InteractsWithElementIndexes;
    use RespondsWithFlash;

    public function __construct(
        private Request $request,
        private ElementActions $elementActions,
        private ElementSources $elementSources,
        private TranslationI18N $i18N,
        private ElementExporters $elementExporters,
    ) {}

    public function __invoke(): SymfonyResponse
    {
        $validated = $this->request->validate([
            'elementType' => [
                'required',
                'string',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (! is_string($value) || ! is_subclass_of($value, ElementInterface::class)) {
                        $fail(new InvalidTypeException((string) $value, ElementInterface::class)->getMessage());
                    }
                },
            ],
            'elementAction' => ['required', 'string'],
            'elementIds' => ['required', 'array'],
        ]);

        /** @var class-string<ElementInterface> $elementType */
        $elementType = $validated['elementType'];
        $actionClass = $validated['elementAction'];
        $elementIds = $validated['elementIds'];
        $context = $this->request->input('context', ElementSources::CONTEXT_INDEX);

        [$sourceKey, $source] = $this->source($elementType, $this->request->input('source'), $context);
        $condition = $this->condition();
        $viewState = $this->viewState();
        $elementQuery = $this->elementQuery($elementType, $source, $condition);

        $actions = null;
        $exporters = null;

        if ($this->isAdministrative($context) && isset($sourceKey)) {
            $actions = $this->availableActions($elementType, $sourceKey, $elementQuery);
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

        $responseData = $this->elementResponseData(
            elementType: $elementType,
            elementQuery: $elementQuery,
            viewState: $viewState,
            sourceKey: $sourceKey,
            context: $context,
            actions: $actions,
            exporters: $exporters,
            includeContainer: true,
            includeActions: true,
        );

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

    /**
     * @param  class-string<ElementInterface>  $elementType
     */
    private function availableActions(
        string $elementType,
        string $sourceKey,
        ElementQueryInterface $elementQuery,
    ): array {
        return $this->elementActions->availableActions($elementType, $sourceKey, $elementQuery);
    }

    /**
     * @param  class-string<ElementInterface>  $elementType
     * @return ElementExporterInterface[]|null
     */
    private function availableExporters(string $elementType, string $sourceKey): ?array
    {
        if ($this->request->isMobileBrowser()) {
            return null;
        }

        return $this->elementExporters->availableExporters($elementType, $sourceKey);
    }

    /**
     * @param  class-string<ElementInterface>  $elementType
     * @param  ElementExporterInterface[]|null  $exporters
     */
    private function elementResponseData(
        string $elementType,
        ElementQueryInterface $elementQuery,
        array $viewState,
        ?string $sourceKey,
        string $context,
        ?array $actions,
        ?array $exporters,
        bool $includeContainer,
        bool $includeActions,
    ): array {
        $responseData = [];

        if ($includeActions) {
            $responseData['actions'] = $viewState['static'] === true ? [] : $this->actionData($actions);
            $responseData['actionsHeadHtml'] = HtmlStack::headHtml();
            $responseData['actionsBodyHtml'] = HtmlStack::bodyHtml();
            $responseData['exporters'] = $this->exporterData($exporters);
        }

        $disabledElementIds = $this->request->input('disabledElementIds', []);
        $selectable = (
            ((! empty($actions)) || $this->request->boolean('selectable')) &&
            empty($viewState['inlineEditing'])
        );
        $sortable = $this->isAdministrative($context) && $this->request->boolean('sortable');

        if ($sourceKey) {
            $responseData['html'] = $elementType::indexHtml(
                $elementQuery,
                $disabledElementIds,
                $viewState,
                $sourceKey,
                $context,
                $includeContainer,
                $selectable,
                $sortable,
            );
            $responseData['headHtml'] = HtmlStack::headHtml();
            $responseData['bodyHtml'] = HtmlStack::bodyHtml();

            return $responseData;
        }

        $responseData['html'] = Html::tag('div', t('Nothing yet.'), [
            'class' => ['zilch', 'small'],
        ]);

        return $responseData;
    }

    private function actionData(?array $actions): ?array
    {
        if (empty($actions)) {
            return null;
        }

        return $this->elementActions->serializeActions($actions);
    }

    /**
     * @param  ElementExporterInterface[]|null  $exporters
     */
    private function exporterData(?array $exporters): ?array
    {
        if (empty($exporters)) {
            return null;
        }

        return $this->elementExporters->serializeExporters($exporters);
    }
}
