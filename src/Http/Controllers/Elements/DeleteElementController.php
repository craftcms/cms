<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Elements;

use CraftCms\Cms\Element\Elements;
use CraftCms\Cms\Http\Requests\ElementRequest;
use CraftCms\Cms\Http\Responses\ElementResponse;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

use function CraftCms\Cms\t;

class DeleteElementController
{
    public function __construct(
        private readonly ElementRequest $request,
        private readonly Elements $elements,
    ) {}

    public function destroy(): Response
    {
        $element = $this->request->element();

        // If this is a provisional draft, delete the canonical
        if ($element && $element->isProvisionalDraft) {
            $element = $element->getCanonical(true);
        }

        if (! $element || $element->getIsDraft() || $element->getIsRevision()) {
            abort(400, 'No element was identified by the request.');
        }

        Gate::authorize('delete', $element);

        if (! $this->elements->deleteElement($element)) {
            return new ElementResponse()->failure($element, t('Couldn’t delete {type}.', [
                'type' => $element::lowerDisplayName(),
            ]));
        }

        return new ElementResponse()->success($element, t('{type} deleted.', [
            'type' => $element::displayName(),
        ]));
    }

    public function destroyForSite(): Response
    {
        $element = $this->request->element(checkForProvisionalDraft: true);

        if (! $element || $element->getIsRevision()) {
            abort(400, 'No element was identified by the request.');
        }

        Gate::authorize('deleteForSite', $element);

        if ($element->isProvisionalDraft) {
            $canonical = $element->getCanonical();

            if ($canonical->id === $element->id) {
                $canonical = null;
            }

            if ($canonical) {
                Gate::authorize('deleteForSite', $canonical);
            }
        } else {
            $canonical = null;
        }

        $this->elements->deleteElementForSite($element);

        if ($canonical) {
            $this->elements->deleteElementForSite($canonical);
            $element = $canonical;
        }

        return new ElementResponse()->success($element, t('{type} deleted for site.', [
            'type' => $element->getIsDraft() && ! $element->isProvisionalDraft
                ? t('Draft')
                : $element::displayName(),
        ]));
    }
}
