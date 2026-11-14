<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Elements;

use CraftCms\Cms\Element\CurrentElementIndex;
use CraftCms\Cms\Http\Requests\ElementIndexRequest;
use CraftCms\Cms\Http\ViewModels\ModalIndexViewModel;
use Illuminate\Http\JsonResponse;

use function CraftCms\Cms\t;

/**
 * @since 6.0.0
 */
readonly class ElementSelectorModalController
{
    public function __invoke(ElementIndexRequest $request, CurrentElementIndex $currentElementIndex): JsonResponse
    {
        $request->validate([
            'sources' => ['nullable', function (string $attribute, mixed $value, $fail): void {
                if (! is_array($value) && ! is_string($value)) {
                    $fail(t('The {attribute} field must be a string or array.', ['attribute' => $attribute]));
                }
            }],
            'sources.*' => ['string'],
            'readOnly' => ['nullable', 'boolean'],
        ]);

        $elementType = $request->elementType();
        $currentElementIndex->activate();
        $sources = $request->input('sources');

        return new JsonResponse([
            'props' => new ModalIndexViewModel(
                $elementType,
                $request,
                restrictToSources: is_array($sources) ? array_values($sources) : null,
                readOnly: $request->boolean('readOnly'),
            )->toArray(),
        ]);
    }
}
