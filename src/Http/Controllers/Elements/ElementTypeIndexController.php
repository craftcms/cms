<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Elements;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\ElementIndexReturnUrl;
use CraftCms\Cms\Http\Requests\ElementIndexRequest;
use CraftCms\Cms\Http\ViewModels\ElementTypeIndexViewModel;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

use function CraftCms\Cms\t;

/**
 * Renders the element index for the element type named by the route's `elementType` default.
 *
 * A `newElementRoute` default names the route that creates a new element
 * ({@see NewElementController}), which the index links to.
 *
 * ```php
 * Route::get('customers', ElementTypeIndexController::class)
 *     ->defaults('elementType', Customer::class)
 *     ->defaults('newElementRoute', 'customers.new');
 * ```
 *
 * @since 6.0.0
 */
readonly class ElementTypeIndexController
{
    public function __invoke(ElementIndexRequest $request): Response
    {
        $elementType = $request->route('elementType');

        abort_unless(is_string($elementType) && is_subclass_of($elementType, ElementInterface::class), 404);

        $viewModel = new ElementTypeIndexViewModel($elementType, $request);
        ElementIndexReturnUrl::remember($elementType, $request);

        return Inertia::render('elements/Index', [$viewModel])
            ->with([
                'indexUrl' => $request->url(),
                'newElement' => $this->newElement($elementType, $request->route('newElementRoute')),
            ]);
    }

    /**
     * The button for creating an element, when the index has a route for it and the user may create one.
     *
     * @param  class-string<ElementInterface>  $elementType
     * @return array{label: string, href: string}|null
     */
    private function newElement(string $elementType, mixed $routeName): ?array
    {
        if (! is_string($routeName) || Gate::denies('save', new $elementType)) {
            return null;
        }

        return [
            'label' => t('New {type}', ['type' => $elementType::lowerDisplayName()]),
            'href' => route($routeName),
        ];
    }
}
