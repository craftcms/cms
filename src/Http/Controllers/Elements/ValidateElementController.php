<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Elements;

use CraftCms\Cms\Element\Validation\ElementRules;
use CraftCms\Cms\Http\Requests\ElementRequest;
use CraftCms\Cms\Http\Responses\ElementResponse;
use Symfony\Component\HttpFoundation\Response;

use function CraftCms\Cms\t;

readonly class ValidateElementController
{
    public function __construct(
        private ElementRequest $request,
    ) {}

    public function __invoke(): Response
    {
        $element = $this->request->element();

        // this can happen if we're creating e.g. nested entry in a matrix field (cards or element index)
        // and we hit "create entry" before the autosave kicks in
        if ($element instanceof Response) {
            return $element;
        }

        if (! $element || $element->getIsRevision()) {
            abort(400, 'No element was identified by the request.');
        }

        $element->ruleset->useScenario(ElementRules::SCENARIO_LIVE);

        if (! $element->validate()) {
            return new ElementResponse()->failure($element, t('{type} validation failed.', [
                'type' => $element::displayName(),
            ]));
        }

        return new ElementResponse()->success($element, t('{type} validation successful.', [
            'type' => $element::displayName(),
        ]));
    }
}
