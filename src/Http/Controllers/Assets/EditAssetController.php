<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Assets;

use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Http\Controllers\Elements\Concerns\SavesElement;
use CraftCms\Cms\Http\Requests\ElementRequest;
use CraftCms\Cms\Http\ViewModels\AssetEditViewModel;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Renders the Inertia asset edit screen.
 *
 * The legacy `EditElementController` still serves slideouts and the element
 * types that haven't been ported.
 */
class EditAssetController
{
    use SavesElement;

    public function __construct(
        protected readonly ElementRequest $request,
    ) {}

    public function __invoke(): Response|InertiaResponse
    {
        $element = $this->request->element(
            ['id' => $this->request->route('id') ?? $this->request->integer('elementId')],
            strictSite: false,
        );

        if ($element instanceof Response) {
            return $element;
        }

        if (! $element instanceof Asset) {
            abort(400, 'No asset was identified by the request.');
        }

        $this->applyParamsToElement($element);

        return Inertia::render('assets/Edit', new AssetEditViewModel(
            asset: $element,
            request: $this->request,
            canSave: $this->canSave($element, $this->request->craftUser()),
        ));
    }
}
