<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Elements;

use CraftCms\Cms\Element\ElementHelper;
use CraftCms\Cms\Http\Controllers\Elements\Concerns\EditsElement;
use CraftCms\Cms\Http\Requests\ElementRequest;
use CraftCms\Cms\Support\Facades\HtmlStack;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class PreviewElementController
{
    use EditsElement;

    public function __construct(
        protected readonly ElementRequest $request,
    ) {}

    public function __invoke(int $id, ?string $slug = null): Response|View
    {
        $element = $this->request->element([
            'id' => $id,
        ], checkForProvisionalDraft: true);

        if ($element instanceof Response) {
            return $element;
        }

        abort_if(is_null($element), 400, 'No element was identified by the request.');

        $redirectUrl = $this->request->getSigned('returnUrl', ElementHelper::postEditUrl($element));

        HtmlStack::jsWithVars(fn (
            $elementType,
            $elementId,
            $draftId,
            $revisionId,
            $siteId,
            $redirectUrl,
        ) => <<<JS
        (() => {
          const preview = new Craft.Preview({
            elementType: $elementType,
            elementId: $elementId,
            draftId: $draftId,
            revisionId: $revisionId,
            siteId: $siteId,
            standaloneMode: true,
            redirectUrl: $redirectUrl,
          })
          preview.open();
        })();
        JS, [
            $element::class,
            $element->isProvisionalDraft ? $element->getCanonicalId() : $element->id,
            ! $element->isProvisionalDraft ? $element->draftId : null,
            $element->revisionId,
            $element->siteId,
            $redirectUrl,
        ]);

        [$docTitle, $title] = $this->editElementTitles($element);

        return view('_layouts/base', compact('docTitle', 'title'));
    }
}
