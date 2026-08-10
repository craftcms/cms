<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Entries;

use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Http\Controllers\Elements\Concerns\SavesElement;
use CraftCms\Cms\Http\Controllers\Elements\EditElementController;
use CraftCms\Cms\Http\Requests\ElementRequest;
use CraftCms\Cms\Http\Responses\CpScreenResponse;
use CraftCms\Cms\Http\ViewModels\EntryEditViewModel;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Renders the Inertia entry edit screen.
 *
 * Drafts and revisions still fall through to the legacy
 * {@see EditElementController}, which keeps their notices, apply/revert
 * controls, and canonical-change merging intact until those screens are ported.
 * The legacy controller also continues to serve slideouts and the element types
 * that haven't been ported yet.
 */
class EditEntryController
{
    use SavesElement;

    public function __construct(
        protected readonly ElementRequest $request,
    ) {}

    public function __invoke(): Response|CpScreenResponse|InertiaResponse
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

        // Visiting a canonical entry resolves to the author's provisional draft
        // when one exists, so this covers provisional drafts too.
        if ($element->getIsDraft() || $element->getIsRevision()) {
            return app(EditElementController::class)->setElement($element)();
        }

        $this->applyParamsToElement($element);

        return Inertia::render('content/Edit', new EntryEditViewModel(
            entry: $element,
            request: $this->request,
            canSave: $this->canSave($element, $this->request->craftUser()),
        ));
    }
}
