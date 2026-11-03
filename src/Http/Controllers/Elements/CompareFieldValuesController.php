<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Elements;

use CraftCms\Cms\Element\Enums\AttributeStatus;
use CraftCms\Cms\Element\FieldValueComparisons;
use CraftCms\Cms\FieldLayout\LayoutElements\BaseField;
use CraftCms\Cms\Http\Requests\ElementRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

/**
 * Returns a field’s value on a draft, revision, or nested element, along with the value it’s compared with, for
 * the “Compare changes” field action.
 *
 * @since 6.0.0
 */
readonly class CompareFieldValuesController
{
    public function __construct(
        private ElementRequest $request,
        private FieldValueComparisons $comparisons,
    ) {}

    public function __invoke(): Response
    {
        $element = $this->request->element();

        if ($element instanceof Response) {
            return $element;
        }

        if (! $element || ! FieldValueComparisons::isComparable($element)) {
            abort(400, 'No draft or revision was identified by the request.');
        }

        $this->request->validate([
            'layoutElementUid' => ['required', 'uuid'],
            'status' => ['nullable', Rule::enum(AttributeStatus::class)],
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

        $status = $this->request->enum('status', AttributeStatus::class) ?? AttributeStatus::Modified;

        return new JsonResponse($this->comparisons->compare($element, $layoutElement, $status)->toArray());
    }
}
