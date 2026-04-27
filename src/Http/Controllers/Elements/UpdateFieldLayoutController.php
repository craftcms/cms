<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Elements;

use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Element\Validation\ElementRules;
use CraftCms\Cms\Http\Controllers\Elements\Concerns\CreatesElement;
use CraftCms\Cms\Http\Controllers\Elements\Concerns\SavesElement;
use CraftCms\Cms\Http\Controllers\Elements\Concerns\UpdatesFieldLayout;
use CraftCms\Cms\Http\Requests\ElementRequest;
use CraftCms\Cms\Http\Responses\ElementResponse;
use CraftCms\Cms\View\DeltaRegistry;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

readonly class UpdateFieldLayoutController
{
    use CreatesElement;
    use SavesElement;
    use UpdatesFieldLayout;

    public function __construct(
        protected ElementRequest $request,
        private DeltaRegistry $deltaRegistry,
    ) {}

    public function __invoke(): Response
    {
        if ($this->request->has('elementId') || $this->request->has('elementUid')) {
            $element = $this->request->element();
        } else {
            $element = $this->createElement();
        }

        // Prevalidate?
        if ($this->request->boolean('prevalidate') && $element->enabled && $element->getEnabledForSite()) {
            $element->ruleset->useScenario(ElementRules::SCENARIO_LIVE);
            $element->validate();
        }

        /**
         * see https://github.com/craftcms/cms/issues/14635#issuecomment-2349006694 for details
         *
         * @var Element|Response|null $element
         */
        if ($element instanceof Response) {
            return $element;
        }

        if (! $element || $element->getIsRevision()) {
            abort(400, 'No element was identified by the request.');
        }

        Gate::authorize('view', $element);

        $this->applyParamsToElement($element);

        // Make sure nothing just changed that would prevent the user from saving
        Gate::authorize('view', $element);

        $data = $this->fieldLayoutData($element);

        $data += [
            'initialDeltaValues' => $this->deltaRegistry->getInitialValues(),
        ];

        return new ElementResponse()->success($element, 'Field layout updated.', $data, true);
    }
}
