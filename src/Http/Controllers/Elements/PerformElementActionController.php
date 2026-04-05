<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Elements;

use Closure;
use craft\base\ElementExporterInterface;
use craft\base\ElementInterface;
use CraftCms\Cms\Condition\Conditions;
use CraftCms\Cms\Element\Actions\ElementActions;
use CraftCms\Cms\Element\Conditions\Contracts\ElementConditionInterface;
use CraftCms\Cms\Element\Conditions\ElementCondition;
use CraftCms\Cms\Element\ElementHelper;
use CraftCms\Cms\Element\Elements as ElementElements;
use CraftCms\Cms\Element\ElementSources;
use CraftCms\Cms\Element\Exceptions\InvalidTypeException;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Support\Facades\HtmlStack;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Typecast;
use CraftCms\Cms\Translation\I18N as TranslationI18N;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

use function CraftCms\Cms\t;

readonly class PerformElementActionController
{
    use RespondsWithFlash;

    public function __construct(
        private Request $request,
        private ElementActions $elementActions,
        private ElementSources $elementSources,
        private TranslationI18N $i18N,
        private Conditions $conditions,
        private ElementElements $elements,
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
            return $result['response'];
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

    private function condition(): ?ElementConditionInterface
    {
        /** @var array|null $conditionConfig */
        /** @phpstan-var array{class:class-string<ElementConditionInterface>}|null $conditionConfig */
        $conditionConfig = $this->request->input('condition');

        if (! $conditionConfig) {
            return null;
        }

        /** @var ElementConditionInterface $condition */
        $condition = $this->conditions->createCondition($conditionConfig);

        if ($condition instanceof ElementCondition) {
            $referenceElementId = $this->request->input('referenceElementId');
            if ($referenceElementId) {
                $criteria = [];

                if ($ownerId = $this->request->input('referenceElementOwnerId')) {
                    $criteria['ownerId'] = $ownerId;
                }

                $condition->referenceElement = $this->elements->getElementById(
                    (int) $referenceElementId,
                    siteId: $this->request->input('referenceElementSiteId'),
                    criteria: $criteria,
                );
            }
        }

        return $condition;
    }

    /**
     * @param  class-string<ElementInterface>  $elementType
     * @return array{0:?string,1:?array}
     */
    private function source(string $elementType, ?string $sourceKey, string $context): array
    {
        if (! isset($sourceKey)) {
            return [$sourceKey, null];
        }

        if ($sourceKey === '__IMP__') {
            return [$sourceKey, [
                'type' => ElementSources::TYPE_NATIVE,
                'key' => '__IMP__',
                'label' => t('All elements'),
                'hasThumbs' => $elementType::hasThumbs(),
            ]];
        }

        $source = $this->elementSources->findSource($elementType, $sourceKey, $context);

        if ($source === null) {
            $sourceKey = null;
        }

        return [$sourceKey, $source];
    }

    private function viewState(): array
    {
        $viewState = $this->request->input('viewState', []);

        if (empty($viewState['mode'])) {
            $viewState['mode'] = 'table';
        }

        return $viewState;
    }

    /**
     * @param  class-string<ElementInterface>  $elementType
     */
    private function elementQuery(
        string $elementType,
        ?array $source,
        ?ElementConditionInterface $condition,
    ): ElementQueryInterface {
        $query = $elementType::find();

        if (! $source) {
            $query->id(false);

            return $query;
        }

        if ($source['type'] === ElementSources::TYPE_CUSTOM) {
            $sourceCondition = $this->conditions->createCondition($source['condition']);
            $sourceCondition->modifyQuery($query);
        }

        $applyCriteria = function (array $criteria) use ($query): void {
            if (! $criteria) {
                return;
            }

            if (isset($criteria['trashed'])) {
                $criteria['trashed'] = filter_var($criteria['trashed'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false;
            }

            if (isset($criteria['drafts'])) {
                $criteria['drafts'] = filter_var($criteria['drafts'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false;
            }

            if (isset($criteria['draftOf'])) {
                if (is_numeric($criteria['draftOf']) && $criteria['draftOf'] != 0) {
                    $criteria['draftOf'] = (int) $criteria['draftOf'];
                } else {
                    $criteria['draftOf'] = filter_var($criteria['draftOf'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                }
            }

            Typecast::configure($query, ElementHelper::cleanseQueryCriteria($criteria));
        };

        $applyCriteria($this->request->input('baseCriteria') ?? []);

        if ($condition) {
            $condition->modifyQuery($query);
        }

        $applyCriteria($this->request->input('criteria') ?? []);

        $filterConditionConfig = $this->request->input('filterConfig');
        if (! $filterConditionConfig && $filterConditionStr = $this->request->input('filters')) {
            parse_str((string) $filterConditionStr, $filterConditionConfig);
            $filterConditionConfig = $filterConditionConfig['condition'] ?? null;
        }

        if ($filterConditionConfig) {
            $filterCondition = $this->conditions->createCondition($filterConditionConfig);
            $filterCondition->modifyQuery($query);
        }

        $collapsedElementIds = $this->request->input('collapsedElementIds');

        if (! $collapsedElementIds) {
            return $query;
        }

        $descendantQuery = (clone $query)
            ->offset(null)
            ->limit(null)
            ->reorder()
            ->positionedAfter(null)
            ->positionedBefore(null)
            ->status(null);

        $collapsedElements = (clone $descendantQuery)
            ->id($collapsedElementIds)
            ->orderBy('lft')
            ->all();

        if (empty($collapsedElements)) {
            return $query;
        }

        $descendantIds = [];

        foreach ($collapsedElements as $element) {
            if (in_array($element->id, $descendantIds, false)) {
                continue;
            }

            $descendantIds = array_merge($descendantIds, (clone $descendantQuery)
                ->descendantOf($element)
                ->ids());
        }

        if (empty($descendantIds)) {
            return $query;
        }

        return $query->whereNotIn('elements.id', $descendantIds);
    }

    private function isAdministrative(string $context): bool
    {
        return in_array($context, ['index', 'embedded-index'], true);
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
        if (request()->isMobileBrowser()) {
            return null;
        }

        $exporters = $elementType::exporters($sourceKey);

        foreach ($exporters as $index => $exporter) {
            if ($exporter instanceof ElementExporterInterface) {
                $exporter->setElementType($elementType);

                continue;
            }

            if (is_string($exporter)) {
                $exporter = ['type' => $exporter];
            }

            $exporter['elementType'] = $elementType;
            $exporters[$index] = $this->elements->createExporter($exporter);
        }

        return array_values($exporters);
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

        $data = [];

        foreach ($exporters as $exporter) {
            $data[] = [
                'type' => $exporter::class,
                'name' => $exporter::displayName(),
                'formattable' => $exporter::isFormattable(),
            ];
        }

        return $data;
    }
}
