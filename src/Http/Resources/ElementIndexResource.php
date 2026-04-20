<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Resources;

use CraftCms\Cms\Element\CurrentElementIndex;
use CraftCms\Cms\Http\Controllers\Elements\Concerns\InteractsWithElementIndexes;
use CraftCms\Cms\Http\Requests\ElementRequest;
use CraftCms\Cms\Support\Facades\ElementActions;
use CraftCms\Cms\Support\Facades\ElementExporters;
use CraftCms\Cms\Support\Facades\HtmlStack;
use CraftCms\Cms\Support\Html;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

use function CraftCms\Cms\t;

class ElementIndexResource extends JsonResource
{
    use InteractsWithElementIndexes;

    #[Override]
    public static $wrap = false;

    public function __construct(
        private readonly bool $includeContainer = true,
        private readonly bool $includeActions = true,
    ) {
        parent::__construct(null);
    }

    #[Override]
    public function toArray(Request $_): array
    {
        $request = app(ElementRequest::class);

        $elementType = $request->elementType();
        [$sourceKey, $source] = $this->resolveSource($elementType, $request->input('source'), $request->context());
        $elementQuery = $this->buildElementQueryState(
            elementType: $elementType,
            source: $source,
            condition: $request->condition(),
        )['query'];

        app(CurrentElementIndex::class)->activate($elementQuery);

        $viewState = $this->resolveViewState();

        $responseData = [];
        $actions = null;
        $exporters = null;

        if ($this->includeActions) {
            if ($request->isAdministrative() && isset($sourceKey)) {
                $actions = ElementActions::availableActions($elementType, $sourceKey, $elementQuery);
                $exporters = $this->availableExporters($elementType, $sourceKey);
            }

            $responseData['actions'] = match (true) {
                ($viewState['static'] ?? false) === true => [],
                empty($actions) => null,
                default => ElementActions::serializeActions($actions),
            };

            $responseData['actionsHeadHtml'] = HtmlStack::headHtml();
            $responseData['actionsBodyHtml'] = HtmlStack::bodyHtml();
            $responseData['exporters'] = empty($exporters) ? null : ElementExporters::serializeExporters($exporters);
        }

        if (! $sourceKey) {
            $responseData['html'] = Html::tag('div', t('Nothing yet.'), [
                'class' => ['zilch', 'small'],
            ]);

            return $responseData;
        }

        $responseData['html'] = $elementType::indexHtml(
            elementQuery: $elementQuery,
            disabledElementIds: $request->array('disabledElementIds'),
            viewState: [...$viewState, 'fieldLayouts' => $this->resolveFieldLayouts()],
            sourceKey: $sourceKey,
            context: $request->context(),
            includeContainer: $this->includeContainer,
            selectable: (
                ((! empty($actions)) || request()->boolean('selectable')) &&
                empty($viewState['inlineEditing'])
            ),
            sortable: $request->isAdministrative() && $request->boolean('sortable'),
        );

        $responseData['headHtml'] = HtmlStack::headHtml();
        $responseData['bodyHtml'] = HtmlStack::bodyHtml();

        return $responseData;
    }
}
