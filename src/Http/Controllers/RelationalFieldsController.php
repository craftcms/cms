<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Drafts;
use CraftCms\Cms\Structure\Structures;
use CraftCms\Cms\Support\Facades\HtmlStack;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

use function CraftCms\Cms\template;

readonly class RelationalFieldsController
{
    public function structuredInputHtml(
        Request $request,
        Drafts $drafts,
        Structures $structures,
    ): JsonResponse {
        $request->validate([
            'elementType' => ['required', 'string'],
            'elementIds' => ['nullable', 'array'],
            'siteId' => ['nullable'],
            'branchLimit' => ['nullable', 'integer'],
            'containerId' => ['nullable'],
            'name' => ['nullable'],
            'selectionLabel' => ['nullable'],
        ]);

        $elementType = $request->input('elementType');
        $elementIds = $request->input('elementIds', []);

        $elements = [];

        if (! empty($elementIds)) {
            /** @var ElementInterface[] $elements */
            $elements = $elementType::find()
                ->id($elementIds)
                ->siteId($request->input('siteId'))
                ->status(null)
                ->all();

            // Fill in the gaps
            $structures->fillGapsInElements($elements);

            // Enforce the branch limit
            if ($branchLimit = $request->integer('branchLimit')) {
                $structures->applyBranchLimitToElements($elements, $branchLimit);
            }
        }

        $drafts->loadProvisionalChanges($elements);

        $html = template('_includes/forms/elementSelect', [
            'elements' => $elements,
            'id' => $request->input('containerId'),
            'name' => $request->input('name'),
            'selectionLabel' => $request->input('selectionLabel'),
            'elementType' => $elementType,
            'maintainHierarchy' => true,
            'registerJs' => false,
        ]);

        return new JsonResponse([
            'html' => $html,
            'headHtml' => HtmlStack::headHtml(),
            'bodyHtml' => HtmlStack::bodyHtml(),
        ]);
    }
}
