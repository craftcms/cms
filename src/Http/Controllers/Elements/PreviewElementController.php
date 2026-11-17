<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Elements;

use CraftCms\Cms\Element\ElementHelper;
use CraftCms\Cms\Http\Requests\ElementRequest;
use CraftCms\Cms\Http\Responses\CpScreenResponse;
use Illuminate\Support\Facades\Crypt;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * The element's edit screen with Live Preview open and nothing else to go back
 * to, for linking straight into a preview. Saving leaves for `returnUrl`.
 *
 * @since 6.0.0
 */
class PreviewElementController
{
    public function __construct(
        protected readonly ElementRequest $request,
    ) {}

    public function __invoke(int $id, ?string $slug = null): Response|CpScreenResponse|InertiaResponse
    {
        $element = $this->request->element([
            'id' => $id,
        ], checkForProvisionalDraft: true);

        if ($element instanceof Response) {
            return $element;
        }

        abort_if(is_null($element), 400, 'No element was identified by the request.');

        $response = app(EditElementController::class)->setElement($element)();

        if (! $response instanceof InertiaResponse) {
            return $response;
        }

        $redirectUrl = $this->request->getSigned('returnUrl', ElementHelper::postEditUrl($element));

        return $response->with('standalonePreview', [
            'redirect' => Crypt::encrypt($redirectUrl),
            'editUrl' => $element->getCpEditUrl(),
        ]);
    }
}
