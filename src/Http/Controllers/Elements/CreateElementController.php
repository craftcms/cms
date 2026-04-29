<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Elements;

use CraftCms\Cms\Element\Drafts;
use CraftCms\Cms\Element\Validation\ElementRules;
use CraftCms\Cms\Http\Controllers\Elements\Concerns\CreatesElement;
use CraftCms\Cms\Http\Requests\ElementRequest;
use CraftCms\Cms\Http\Responses\ElementResponse;
use CraftCms\Cms\Support\Url;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

use function CraftCms\Cms\t;

readonly class CreateElementController
{
    use CreatesElement;

    public function __construct(
        protected ElementRequest $request,
    ) {}

    public function __invoke(Request $request, Drafts $drafts): Response
    {
        $element = $this->createElement();
        $element->ruleset->useScenario(ElementRules::SCENARIO_ESSENTIALS);

        if (! $drafts->saveElementAsDraft($element, $request->user()->id, markAsSaved: false)) {
            return new ElementResponse()->failure($element, mb_ucfirst(t('Couldn’t create {type}.', [
                'type' => $element::lowerDisplayName(),
            ])));
        }

        // Redirect to its edit page
        $editUrl = $element->getCpEditUrl() ?? Url::actionUrl('elements/edit', [
            'draftId' => $element->draftId,
            'siteId' => $element->siteId,
        ]);

        $response = new ElementResponse()->success($element, t('{type} created.', [
            'type' => t('Draft'),
        ]), array_filter([
            'cpEditUrl' => $this->request->isCpRequest ? $editUrl : null,
        ]));

        if (! $this->request->acceptsJson()) {
            return redirect(Url::urlWithParams($editUrl, ['fresh' => '1']));
        }

        return $response;
    }
}
