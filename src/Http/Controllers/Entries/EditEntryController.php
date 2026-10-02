<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Entries;

use CraftCms\Cms\Auth\SessionAuth;
use CraftCms\Cms\Element\ElementHelper;
use CraftCms\Cms\Element\Elements;
use CraftCms\Cms\Element\Validation\ElementRules;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Http\Controllers\Elements\Concerns\SavesElement;
use CraftCms\Cms\Http\Requests\ElementRequest;
use CraftCms\Cms\Http\ViewModels\EntryEditViewModel;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Renders the Inertia entry edit screen for the canonical entry, its drafts,
 * and its revisions.
 *
 * The legacy `EditElementController` still serves the jQuery slideouts and
 * the element types that haven't been ported.
 *
 * @since 6.0.0
 */
class EditEntryController
{
    use SavesElement;

    public function __construct(
        protected readonly ElementRequest $request,
        private readonly Elements $elements,
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

        if (! $element instanceof Entry) {
            abort(400, 'No entry was identified by the request.');
        }

        return $this->render($element);
    }

    /**
     * Renders the edit screen for an entry that's already been resolved.
     *
     * {@see EditElementController} hands entries over to this when a Vue
     * slideout asks for them, so nested entries get the same editor as the rest.
     */
    public function render(Entry $element): InertiaResponse
    {
        // A draft that has fallen behind its canonical entry picks up the newer
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

        return Inertia::render('content/Edit', new EntryEditViewModel(
            entry: $element,
            request: $this->request,
            canSave: $this->canSave($element, $this->request->craftUser()),
            mergedCanonicalChanges: $mergedCanonicalChanges,
        ));
    }
}
