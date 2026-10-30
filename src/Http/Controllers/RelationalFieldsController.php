<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Drafts;
use CraftCms\Cms\Element\Validation\Rules\ElementTypeRule;
use CraftCms\Cms\Structure\Structures;
use CraftCms\Cms\Support\Facades\HtmlStack;
use CraftCms\Cms\Ui\Controls\ElementSelect;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

use function CraftCms\Cms\template;

/**
 * @since 6.0.0
 */
readonly class RelationalFieldsController
{
    public function structuredInputHtml(
        Request $request,
        Drafts $drafts,
        Structures $structures,
    ): JsonResponse {
        $request->validate([
            'elementType' => ['required', 'string', new ElementTypeRule],
            'elementIds' => ['nullable', 'array'],
            'siteId' => ['nullable'],
            'branchLimit' => ['nullable', 'integer'],
            'containerId' => ['nullable'],
            'name' => ['nullable'],
            'selectionLabel' => ['nullable'],
        ]);

        $elements = $this->normalizedElements($request, $structures);

        $drafts->loadProvisionalChanges($elements);

        $html = template('_includes/forms/elementSelect', [
            'elements' => $elements,
            'id' => $request->input('containerId'),
            'name' => $request->input('name'),
            'selectionLabel' => $request->input('selectionLabel'),
            'elementType' => $request->input('elementType'),
            'maintainHierarchy' => true,
            'registerJs' => false,
        ]);

        return new JsonResponse([
            'html' => $html,
            'headHtml' => HtmlStack::headHtml(),
            'bodyHtml' => HtmlStack::bodyHtml(),
        ]);
    }

    /**
     * The selection of a relation field that maintains hierarchy, in structure
     * order with any missing ancestors filled in and the branch limit applied,
     * in the shape the {@see ElementSelect} control draws from.
     */
    public function structuredElements(Request $request, Structures $structures): JsonResponse
    {
        $request->validate([
            'elementType' => ['required', 'string', new ElementTypeRule],
            'elementIds' => ['nullable', 'array'],
            'elementIds.*' => ['integer'],
            'siteId' => ['nullable', 'integer'],
            'branchLimit' => ['nullable', 'integer'],
        ]);

        return new JsonResponse([
            'elements' => array_map(
                fn (ElementInterface $element): array => ElementSelect::presentation($element),
                $this->normalizedElements($request, $structures),
            ),
        ]);
    }

    /** @return list<ElementInterface> */
    private function normalizedElements(Request $request, Structures $structures): array
    {
        /** @var class-string<ElementInterface> $elementType */
        $elementType = $request->input('elementType');
        $elementIds = $request->input('elementIds', []);

        if (empty($elementIds)) {
            return [];
        }

        $elements = $elementType::find()
            ->id($elementIds)
            ->siteId($request->input('siteId'))
            ->status(null)
            ->withStructure()
            ->all();

        // Fill in the gaps
        $structures->fillGapsInElements($elements);

        // Enforce the branch limit
        if ($branchLimit = $request->integer('branchLimit')) {
            $structures->applyBranchLimitToElements($elements, $branchLimit);
        }

        return array_values($elements);
    }
}
