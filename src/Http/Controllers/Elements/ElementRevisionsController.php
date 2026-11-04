<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Elements;

use CraftCms\Cms\Cms;
use CraftCms\Cms\Cp\Data\ActionItem;
use CraftCms\Cms\Cp\Html\ElementHtml;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\ElementActivity;
use CraftCms\Cms\Element\Enums\ElementActivityType;
use CraftCms\Cms\Element\Revisions;
use CraftCms\Cms\Http\Controllers\Elements\Concerns\ElementCrumbs;
use CraftCms\Cms\Http\Requests\ElementRequest;
use CraftCms\Cms\Http\Requests\TableRequest;
use CraftCms\Cms\Http\Responses\CpScreenResponse;
use CraftCms\Cms\Http\Responses\ElementResponse;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\I18N;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Query;
use CraftCms\Cms\Translation\Locale;
use CraftCms\Cms\Ui\Nodes\Table;
use CraftCms\Cms\Ui\Ui;
use DateTimeInterface;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

use function CraftCms\Cms\t;

/**
 * @since 6.0.0
 */
class ElementRevisionsController
{
    use ElementCrumbs;

    public function __construct(
        private readonly ElementRequest $request,
    ) {}

    public function index(TableRequest $tableRequest, ElementHtml $elementHtml): CpScreenResponse
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

        $pageParam = Cms::config()->getPageTriggerParam();
        $paginator = $element::find()
            ->revisionOf($element)
            ->site('*')
            ->preferSites([$element->siteId])
            ->unique()
            ->status(null)
            ->whereNot('elements.dateCreated', Query::prepareDateForDb($element->dateUpdated))
            ->with(['revisionCreator'])
            ->paginate($tableRequest->limit(), pageName: $pageParam, page: $tableRequest->page())
            ->appends($tableRequest->except($pageParam));

        $table = Table::make('revisions')
            ->columns([
                ['key' => 'revision', 'label' => t('Revision')],
                ['key' => 'dateCreated', 'label' => t('Created at')],
                ['key' => 'creator', 'label' => t('Created by')],
                ['key' => 'notes', 'label' => t('Notes')],
            ])
            ->rows($paginator->getCollection()->map(function (ElementInterface $revision) use ($elementHtml): array {
                $creator = $revision->getCreator();

                return [
                    'id' => $revision->id,
                    'revision' => [
                        'label' => $revision->getRevisionLabel(),
                        'url' => $revision->getCpEditUrl(),
                    ],
                    'dateCreated' => ['html' => Html::tag(
                        'time',
                        Html::encode(I18N::getFormatter()->asDatetime($revision->dateCreated, Locale::LENGTH_SHORT, withTimeZone: true)),
                        ['datetime' => $revision->dateCreated->format(DateTimeInterface::ATOM)],
                    )],
                    'creator' => $creator ? ['html' => $elementHtml->elementChipHtml($creator)] : null,
                    'notes' => $revision->revisionNotes,
                ];
            }))
            ->perPage($paginator->perPage())
            ->pagination(Arr::only($paginator->toArray(), [
                'total', 'per_page', 'current_page', 'last_page',
                'next_page_url', 'prev_page_url', 'from', 'to',
            ]))
            ->emptyMessage(t('This {type} doesn’t have revisions.', [
                'type' => $element::lowerDisplayName(),
            ]));

        return new CpScreenResponse()
            ->title(t('Revisions for “{title}”', [
                'title' => $element->getUiLabel(),
            ]))
            ->crumbs([
                ...$this->crumbs($element),
                new ActionItem()->label(t('Revisions')),
            ])
            ->ui(Ui::make([$table]));
    }

    public function revert(Revisions $revisions, ElementActivity $elementActivity): Response
    {
        $element = $this->request->element();

        if (! $element || ! $element->getIsRevision()) {
            abort(400, 'No revision was identified by the request.');
        }

        Gate::authorize('save', $element->getCanonical(true));

        $canonical = $revisions->revertToRevision($element, $this->request->craftUser()?->getCraftUserId());

        $elementActivity->trackActivity($canonical, ElementActivityType::Save);

        return new ElementResponse()->success($canonical, t('{type} reverted to past revision.', [
            'type' => $element::displayName(),
        ]));
    }
}
