<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Elements;

use CraftCms\Cms\Http\Controllers\Elements\Concerns\ElementCrumbs;
use CraftCms\Cms\Http\Requests\ElementRequest;
use CraftCms\Cms\Http\Responses\CpScreenResponse;
use CraftCms\Cms\Support\Query;

use function CraftCms\Cms\t;

class ElementRevisionsController
{
    use ElementCrumbs;

    public function __construct(
        private readonly ElementRequest $request,
    ) {}

    public function index(): CpScreenResponse
    {
        $element = $this->request->element([
            'id' => $this->request->route('id'),
        ]);

        if ($element->getIsUnpublishedDraft()) {
            abort(400, 'Unpublished drafts don\'t have revisions');
        }

        if (! $element->hasRevisions()) {
            abort(400, 'Element doesn\'t have revisions');
        }

        return new CpScreenResponse()
            ->title(t('Revisions for “{title}”', [
                'title' => $element->getUiLabel(),
            ]))
            ->crumbs([
                ...$this->crumbs($element, current: false),
                [
                    'label' => t('Revisions'),
                    'current' => true,
                ],
            ])
            ->contentTemplate('_elements/revisions', [
                'element' => $element,
                'revisionsQuery' => $element::find()
                    ->revisionOf($element)
                    ->site('*')
                    ->preferSites([$element->siteId])
                    ->unique()
                    ->status(null)
                    ->whereNot('elements.dateCreated', Query::prepareDateForDb($element->dateUpdated))
                    ->with(['revisionCreator']),
            ]);
    }
}
