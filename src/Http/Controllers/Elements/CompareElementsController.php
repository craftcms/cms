<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Elements;

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Data\FieldComparisonRow;
use CraftCms\Cms\Element\Events\ElementEditorPayloadResolving;
use CraftCms\Cms\Element\FieldChangeComparisons;
use CraftCms\Cms\Http\Controllers\Elements\Concerns\ElementCrumbs;
use CraftCms\Cms\Http\Requests\ElementRequest;
use CraftCms\Cms\Http\Responses\CpScreenResponse;
use CraftCms\Cms\Site\Sites;
use CraftCms\Cms\Support\Url;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

use function CraftCms\Cms\t;

/**
 * The Compare screen, which shows two versions of an element side by side, field by field: a draft and the current
 * element, two revisions, or the element in two sites. Element types offer it by linking to it; each side gets a
 * version menu when the element type has drafts or revisions, and a site menu when it’s localized.
 *
 * The newer version is identified like the editor’s, by `draftId`, `revisionId`, and `siteId`. The one it’s compared
 * with is identified by `from` (`current`, `draft-{draftId}`, or `revision-{revisionId}`) and `fromSiteId`, and
 * defaults to what the newer version was based on.
 *
 * The newer version is edited in place when it can be — the current element or a draft the user can save, but never
 * a revision — through the same editor payload as its edit page, and saving it comes back here.
 *
 * @since 6.0.0
 */
readonly class CompareElementsController
{
    use ElementCrumbs;

    /** How many recent revisions the version pickers list. */
    private const int MAX_REVISIONS = 10;

    public function __construct(
        private FieldChangeComparisons $comparisons,
        private Sites $sites,
    ) {}

    public function __invoke(ElementRequest $request): CpScreenResponse|Response
    {
        // As on the edit screen, the current element is shown with the user’s unsaved edits, when they have any,
        // unless the request asks for it as it’s saved.
        $to = $request->element(['id' => $request->route('id')], checkForProvisionalDraft: $request->query('edits') !== '0');

        if ($to instanceof Response) {
            return $to;
        }

        if (! $to || ! $to->id || $to->getIsUnpublishedDraft()) {
            abort(400, 'No element was identified by the request.');
        }

        $canonical = $to->getCanonical(true);
        $edits = match (true) {
            $to->isProvisionalDraft => $to,
            $to::hasDrafts() => $this->edits($request, $canonical, $to->siteId),
            default => null,
        };
        $hasEdits = $edits !== null;
        $from = $this->fromVersion($request, $to, $canonical);
        $versions = $this->versions($request, $canonical, $edits, [$from, $to]);
        // A site only identifies a side of a localized element.
        $localized = $to::isLocalized();
        $state = [
            'from' => ['version' => $this->versionKey($from), 'siteId' => $localized ? $from->siteId : null],
            'to' => ['version' => $this->versionKey($to), 'siteId' => $localized ? $to->siteId : null],
        ];

        $comparison = $this->comparisons->compareElements($from, $to);

        return new CpScreenResponse()
            ->title(t('Compare “{title}”', ['title' => $canonical->getUiLabel()]))
            ->crumbs([
                ...$this->crumbs($canonical),
                ['label' => t('Compare'), 'current' => true],
            ])
            ->redirectUrl($this->compareUrl($request, $state, $hasEdits))
            ->inertiaPage('elements/Compare', [
                'from' => [
                    ...$this->side($request, 'from', $from, $state, $versions, $hasEdits),
                    'ui' => $comparison['from'],
                ],
                'to' => [
                    ...$this->side($request, 'to', $to, $state, $versions, $hasEdits),
                    'ui' => $comparison['to'],
                    'editor' => $this->editor($request, $to, $hasEdits),
                ],
                // The sides that badge their changed fields.
                'badged' => $comparison['badged'],
                'rows' => array_map(
                    fn (FieldComparisonRow $row): array => $row->toArray(),
                    $comparison['rows'],
                ),
                'editUrl' => $to->getCpEditUrl(),
                // The same comparison the other way around, which edits the other version, when it can be.
                'reverseUrl' => $state['from'] !== $state['to']
                    ? $this->compareUrl($request, ['from' => $state['to'], 'to' => $state['from']], $hasEdits)
                    : null,
            ]);
    }

    /**
     * The edit screen’s payload for the newer version, when the user can edit it. The current element as it’s saved
     * can’t be, while the user has unsaved edits to it: saving would go into those edits, as it does on the edit
     * screen, which shows them in its place.
     *
     * @return array<string, mixed>|null
     */
    private function editor(ElementRequest $request, ElementInterface $to, bool $hasEdits): ?array
    {
        if (
            $to->getIsRevision() ||
            ($hasEdits && $to->getIsCanonical()) ||
            ! $request->craftUser()->can('save', $to->isProvisionalDraft ? $to->getCanonical(true) : $to)
        ) {
            return null;
        }

        $viewModelClass = $to::editViewModelClass();
        $viewModel = new $viewModelClass($to, $request);

        event($event = new ElementEditorPayloadResolving($to, $viewModel->toArray(), 'main-form'));

        return $event->data;
    }

    /**
     * The version the request compares the newer one with, or what that was based on.
     */
    private function fromVersion(ElementRequest $request, ElementInterface $to, ElementInterface $canonical): ElementInterface
    {
        $version = $request->query('from');
        $siteId = $to::isLocalized() ? ($request->integer('fromSiteId') ?: $to->siteId) : $to->siteId;

        if (! is_string($version) && $siteId === $to->siteId) {
            $from = $this->comparisons->baseOf($to) ?? $to;
        } else {
            if ($siteId !== $to->siteId) {
                abort_unless(in_array($siteId, $this->sites->getEditableSiteIds()->all(), true), 403, 'User not authorized to edit content for this site.');
            }

            [$type, $id] = array_pad(explode('-', is_string($version) ? $version : 'current', 2), 2, null);
            $query = $canonical::find()->siteId($siteId)->status(null);

            $from = match ($type) {
                'draft' => $query->draftId((int) $id)->one(),
                'revision' => $query->revisionId((int) $id)->trashed(null)->one(),
                'current' => $query->id($canonical->id)->one(),
                'edits' => $this->edits($request, $canonical, $siteId),
                default => abort(400, "Invalid version: $version"),
            };

            abort_if($from === null || $from->getCanonicalId() !== $canonical->id, 404, 'The version to compare with doesn’t exist.');
        }

        abort_unless($request->craftUser()->can('view', $from), 403, 'User not authorized to view this element.');

        return $from;
    }

    /**
     * What the screen says about one side of the comparison, with menus that switch it to another version or site and
     * keep the other side as it is.
     *
     * @param  'from'|'to'  $key
     * @param  array{from: array{version: string, siteId: int|null}, to: array{version: string, siteId: int|null}}  $state
     * @param  list<array{key: string, label: string, group: string, badge?: array{label: string, fill: string, icon: string}}>  $versions
     * @return array{label: string, edited: bool, siteName: string|null, versionMenu: list<array<string, mixed>>, siteMenu: list<array<string, mixed>>}
     */
    private function side(ElementRequest $request, string $key, ElementInterface $element, array $state, array $versions, bool $hasEdits): array
    {
        $siteIds = $this->siteIds($element);
        $other = $key === 'from' ? 'to' : 'from';

        // A choice that would show the same site and version as the other side is there, but can’t be made.
        $link = fn (string $label, array $change, bool $selected, ?array $badge = null): array => array_filter(
            ! $selected && array_replace($state[$key], $change) === $state[$other]
                ? [
                    'type' => 'button',
                    'label' => $label,
                    'disabled' => true,
                    'badge' => $badge,
                ]
                : [
                    'type' => 'link',
                    'label' => $label,
                    'href' => $this->compareUrl($request, array_replace_recursive($state, [$key => $change]), $hasEdits),
                    'selected' => $selected,
                    'badge' => $badge,
                ],
            fn (mixed $value): bool => $value !== null,
        );
        $groups = ['current' => null, 'drafts' => t('Drafts'), 'revisions' => t('Revisions')];
        $versionMenu = [];

        foreach ($groups as $group => $heading) {
            $items = array_map(
                fn (array $version): array => $link($version['label'], ['version' => $version['key']], $version['key'] === $state[$key]['version'], $version['badge'] ?? null),
                array_values(array_filter($versions, fn (array $version): bool => $version['group'] === $group)),
            );

            if ($items !== []) {
                $versionMenu[] = ['type' => 'group', 'heading' => $heading, 'items' => $items];
            }
        }

        return [
            // As in the editor, the user’s unsaved edits are the current version, marked “Edited”. Without versions to
            // tell apart, the side is the element.
            'label' => match (true) {
                $versions === [] => $element->getUiLabel(),
                $element->isProvisionalDraft => t('Current'),
                default => $this->comparisons->label($element),
            },
            'edited' => (bool) $element->isProvisionalDraft,
            'siteName' => $siteIds !== [] ? $element->getSite()->getName() : null,
            'versionMenu' => $versionMenu,
            'siteMenu' => array_map(
                fn (int $siteId): array => $link($this->sites->getSiteById($siteId)->getName(), ['siteId' => $siteId], $siteId === $element->siteId),
                $siteIds,
            ),
        ];
    }

    /**
     * The URL of the Compare screen with both sides as given. The newer one is the current element with the user’s
     * unsaved edits unless it says otherwise, so the current element as it’s saved does when there are any.
     *
     * @param  array{from: array{version: string, siteId: int|null}, to: array{version: string, siteId: int|null}}  $state
     */
    private function compareUrl(ElementRequest $request, array $state, bool $hasEdits): string
    {
        [$type, $id] = array_pad(explode('-', $state['to']['version'], 2), 2, null);
        $params = [
            'draftId' => $type === 'draft' ? $id : null,
            'revisionId' => $type === 'revision' ? $id : null,
            'edits' => $type === 'current' && $hasEdits ? 0 : null,
            'siteId' => $state['to']['siteId'],
            'from' => $state['from']['version'],
            'fromSiteId' => $state['from']['siteId'],
        ];

        return Url::urlWithParams($request->url(), array_filter($params, fn (mixed $value): bool => $value !== null));
    }

    /**
     * Identifies a version in the pickers: `current`, the user’s unsaved edits to it (`edits`), `draft-{draftId}`, or
     * `revision-{revisionId}`.
     */
    private function versionKey(ElementInterface $element): string
    {
        return match (true) {
            $element->isProvisionalDraft => 'edits',
            $element->getIsDraft() => "draft-$element->draftId",
            $element->getIsRevision() => "revision-$element->revisionId",
            default => 'current',
        };
    }

    /**
     * The user’s unsaved edits to the current element in a site, if they have any.
     */
    private function edits(ElementRequest $request, ElementInterface $canonical, int $siteId): ?ElementInterface
    {
        return $canonical::find()
            ->provisionalDrafts()
            ->draftOf($canonical)
            ->draftCreator($request->craftUser()->getCraftUserId())
            ->siteId($siteId)
            ->status(null)
            ->one();
    }

    /**
     * The versions either side can show, of the ones the user can view: the current element, along with the user’s
     * unsaved edits to it when there are any, its drafts, and its recent revisions, along with any older revision
     * that’s being compared. None, when the element type has neither drafts nor revisions.
     *
     * @param  list<ElementInterface>  $selected
     * @return list<array{key: string, label: string, group: string, badge?: array{label: string, fill: string, icon: string}}>
     */
    private function versions(ElementRequest $request, ElementInterface $canonical, ?ElementInterface $edits, array $selected): array
    {
        $hasDrafts = $canonical::hasDrafts();
        $hasRevisions = $canonical->hasRevisions();

        if (! $hasDrafts && ! $hasRevisions) {
            return [];
        }

        $user = $request->craftUser();
        $viewable = fn (ElementInterface $element): bool => $user->can('view', $element);

        $drafts = $hasDrafts
            ? $canonical::find()
                ->draftOf($canonical)
                ->siteId($canonical->siteId)
                ->status(null)
                ->orderByDesc('dateUpdated')
                ->get()
            : collect();

        $revisions = $hasRevisions
            ? $canonical::find()
                ->revisionOf($canonical)
                ->siteId($canonical->siteId)
                ->status(null)
                ->limit(self::MAX_REVISIONS)
                ->orderByDesc('dateCreated')
                ->get()
            : collect();

        foreach ($selected as $element) {
            if ($element->getIsRevision() && $revisions->doesntContain(fn (ElementInterface $revision): bool => $revision->revisionId === $element->revisionId)) {
                $revisions->push($element);
            }
        }

        return [
            ...($viewable($canonical) ? [['key' => 'current', 'label' => t('Current'), 'group' => 'current']] : []),
            // Marked as the editor marks it.
            ...($edits && $viewable($edits) ? [[
                'key' => 'edits',
                'label' => t('Current'),
                'group' => 'current',
                'badge' => ['label' => t('Edited'), 'fill' => 'violet', 'icon' => 'pen-circle'],
            ]] : []),
            ...$drafts->filter($viewable)->map(fn (ElementInterface $draft): array => [
                'key' => $this->versionKey($draft),
                'label' => $draft->draftName ?: t('Draft'),
                'group' => 'drafts',
            ])->values()->all(),
            ...$revisions->filter($viewable)->map(fn (ElementInterface $revision): array => [
                'key' => $this->versionKey($revision),
                'label' => $revision->getRevisionLabel(),
                'group' => 'revisions',
            ])->values()->all(),
        ];
    }

    /**
     * The sites a side can show: the ones its version is in that the user can edit, or none when there’s only one, or
     * the element type isn’t localized.
     *
     * @return list<int>
     */
    private function siteIds(ElementInterface $element): array
    {
        if (! $element::isLocalized()) {
            return [];
        }

        $existingSiteIds = DB::table(Table::ELEMENTS_SITES)
            ->where('elementId', $element->id)
            ->pluck('siteId')
            ->map(fn (mixed $siteId): int => (int) $siteId)
            ->all();
        $siteIds = $this->sites->getEditableSiteIds()
            ->filter(fn (int $siteId): bool => in_array($siteId, $existingSiteIds, true))
            ->values()
            ->all();

        return count($siteIds) > 1 ? $siteIds : [];
    }
}
