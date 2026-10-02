<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Elements;

use CraftCms\Cms\Auth\SessionAuth;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\ElementHelper;
use CraftCms\Cms\Element\Elements;
use CraftCms\Cms\Element\Validation\ElementRules;
use CraftCms\Cms\Http\Controllers\Elements\Concerns\SavesElement;
use CraftCms\Cms\Http\Requests\ElementRequest;
use CraftCms\Cms\Http\ViewModels\ElementEditViewModel;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Renders an element type's Inertia edit screen for the canonical element, its
 * drafts, and its revisions.
 *
 * Element types opt in via {@see ElementInterface::editControllerClass()}, which
 * is how {@see EditElementController} hands them over when a Vue slideout or a
 * full page load of an `elements/edit` URL asks for one.
 *
 * @template TElement of ElementInterface
 *
 * @since 6.0.0
 */
abstract class ElementEditorController
{
    use SavesElement;

    public function __construct(
        protected readonly ElementRequest $request,
        protected readonly Elements $elements,
    ) {}

    public function __invoke(): Response|InertiaResponse
    {
        $element = $this->request->element(
            ['id' => $this->request->route('id') ?? $this->request->integer('elementId')],
            checkForProvisionalDraft: true,
            strictSite: false,
        );

        if ($element instanceof Response) {
            return $element;
        }

        if (! $element instanceof ElementInterface || ! is_a($element, $this->elementType())) {
            abort(400, sprintf('No %s was identified by the request.', $this->elementType()::lowerDisplayName()));
        }

        return $this->render($element);
    }

    /**
     * Renders the edit screen for an element that's already been resolved.
     *
     * @param  TElement  $element
     */
    public function render(ElementInterface $element): InertiaResponse
    {
        // A draft that has fallen behind its canonical element picks up the newer
        // changes before rendering, and says so.
        $mergedCanonicalChanges = (
            $element::trackChanges() &&
            $element->getIsDraft() &&
            ! $element->getIsUnpublishedDraft() &&
            ElementHelper::isOutdated($element)
        );

        if ($mergedCanonicalChanges) {
            $this->elements->mergeCanonicalChanges($element);
        }

        $this->applyParamsToElement($element);

        if ($this->request->boolean('prevalidate') && $element->enabled && $element->getEnabledForSite()) {
            $element->ruleset->useScenario(ElementRules::SCENARIO_LIVE);
            $element->validate();
        }

        // Minting a preview token later requires the session to be authorized
        // for whatever is being previewed.
        if ($element->id && $element->getPreviewTargets() !== []) {
            match (true) {
                $element->getIsDraft() && ! $element->isProvisionalDraft => SessionAuth::authorize("previewDraft:$element->draftId"),
                $element->getIsRevision() => SessionAuth::authorize("previewRevision:$element->revisionId"),
                default => SessionAuth::authorize('previewElement:'.$element->getCanonicalId()),
            };
        }

        return Inertia::render($this->component(), $this->viewModel(
            $element,
            $this->canSave($element, $this->request->craftUser()),
            $mergedCanonicalChanges,
        ));
    }

    /**
     * The element type this controller edits.
     *
     * @return class-string<TElement>
     */
    abstract protected function elementType(): string;

    /**
     * The Inertia page component that renders the edit screen.
     */
    abstract protected function component(): string;

    /**
     * @param  TElement  $element
     */
    abstract protected function viewModel(ElementInterface $element, bool $canSave, bool $mergedCanonicalChanges): ElementEditViewModel;
}
