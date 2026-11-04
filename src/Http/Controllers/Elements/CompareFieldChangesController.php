<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Elements;

use CraftCms\Cms\Element\FieldChangeComparisons;
use CraftCms\Cms\FieldLayout\LayoutElements\BaseField;
use CraftCms\Cms\Http\Requests\ElementRequest;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Returns how a field on a draft, revision, or nested element differs from what it was based on, for the
 * “Compare changes” field action.
 *
 * @since 6.0.0
 */
readonly class CompareFieldChangesController
{
    public function __construct(
        private ElementRequest $request,
        private FieldChangeComparisons $comparisons,
    ) {}

    public function __invoke(): Response
    {
        $element = $this->request->element();

        if ($element instanceof Response) {
            return $element;
        }

        if (! $element || ! FieldChangeComparisons::isComparable($element)) {
            abort(400, 'No draft or revision was identified by the request.');
        }

        $this->request->validate([
            'layoutElementUid' => ['required', 'uuid'],
        ]);

        $layoutElementUid = $this->request->input('layoutElementUid');
        $layoutElement = $element->getFieldLayout()?->getElementByUid($layoutElementUid);

        if (! $layoutElement instanceof BaseField) {
            abort(400, "Invalid layout element UUID: $layoutElementUid");
        }

        abort_unless(
            $this->request->craftUser()->can('view', $element->getCanonical()),
            403,
            'User not authorized to view this element.',
        );

        $comparison = $this->comparisons->compare($element, $layoutElement);

        abort_if($comparison === null, 400, 'The field isn’t in the element’s form.');

        return new JsonResponse($comparison->toArray());
    }
}
