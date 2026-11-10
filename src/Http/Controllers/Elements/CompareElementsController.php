<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Elements;

use CraftCms\Cms\Cp\Html\PreviewHtml;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Data\FieldComparisonRow;
use CraftCms\Cms\Element\Events\ElementEditorPayloadResolving;
use CraftCms\Cms\Element\FieldChangeComparisons;
use CraftCms\Cms\Field\Contracts\FieldInterface;
use CraftCms\Cms\Http\Controllers\Elements\Concerns\ElementCrumbs;
use CraftCms\Cms\Http\Requests\ElementRequest;
use CraftCms\Cms\Http\Responses\CpScreenResponse;
use CraftCms\Cms\Site\Sites;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Url;
use CraftCms\Cms\Ui\NestedUiPayload;
use CraftCms\Cms\Ui\NodePayload;
use CraftCms\Cms\Ui\UiPayload;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

use function CraftCms\Cms\t;

/**
 * The Compare screen, which shows two versions of an element side by side, field by field: a draft and the current
 * element, two revisions, or the element in two sites. Or, at `compare/{fromId}/{toId}`, two separate elements, as
 * element indexes’ Compare action opens it. Element types offer it by linking to it; each side gets a version menu
 * when the element type has drafts or revisions, and a site menu when it’s localized.
 *
 * The newer version (B, on the right) is identified like the editor’s, by `draftId`, `revisionId`, and `siteId`. The
 * one it’s compared with (A, on the left) is identified by `from` (`current`, `draft-{draftId}`, or
 * `revision-{revisionId}`) and `fromSiteId`. Of one element, A defaults to what B was based on; of two, to the element
 * as `fromId` identifies it. As on the edit screen, the current element is shown with the user’s unsaved edits, when
 * they have any, unless the request asks for it as it’s saved, with `edits=0` for B and `fromEdits=0` for A.
 *
 * Each side is edited in place when the user can save it — the current element or a draft, but never a revision —
 * through the same editor payload as its edit page, and saving it comes back here.
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
        $to = $request->element(['id' => $request->route('toId') ?? $request->route('id')], checkForProvisionalDraft: $request->query('edits') !== '0');

        if ($to instanceof Response) {
            return $to;
        }

        if (! $to || ! $to->id || $to->getIsUnpublishedDraft()) {
            abort(400, 'No element was identified by the request.');
        }

        $fromId = $request->route('fromId');
        $from = $fromId === null
            ? $this->fromVersion($request, $to, $to->getCanonical(true))
            : $this->otherElement($request, (int) $fromId, $to);
        $sides = ['from' => $from, 'to' => $to];
        $canonicals = array_map(fn (ElementInterface $element): ElementInterface => $element->getCanonical(true), $sides);
        // Two separate elements, rather than two versions of one.
        $pair = $canonicals['from']->id !== $canonicals['to']->id;
        $edits = array_map(fn (ElementInterface $element): ?ElementInterface => match (true) {
            $element->isProvisionalDraft => $element,
            $element::hasDrafts() => $this->edits($request, $element->getCanonical(true), $element->siteId),
            default => null,
        }, $sides);
        $hasEdits = array_map(fn (?ElementInterface $edits): bool => $edits !== null, $edits);
        // Each element’s own versions. Of one element, the versions B’s site has.
        $versions = $pair
            ? [
                'from' => $this->versions($request, $canonicals['from'], $edits['from'], [$from]),
                'to' => $this->versions($request, $canonicals['to'], $edits['to'], [$to]),
            ]
            : array_fill_keys(['from', 'to'], $this->versions($request, $canonicals['to'], $edits['to'], [$from, $to]));
        $state = array_map(fn (ElementInterface $element): array => [
            'id' => $element->getCanonicalId(),
            'version' => $this->versionKey($element),
            // A site only identifies a side of a localized element.
            'siteId' => $element::isLocalized() ? $element->siteId : null,
        ], $sides);
        $returnUrl = $this->returnUrl($request);
        // The current element in two sites, without the user’s unsaved edits, is only edited on B’s side: each side
        // would create its own unsaved edits, and the second would replace the first.
        $sameCurrent = $from->getIsCanonical() && $to->getIsCanonical() && $from->id === $to->id;

        $comparison = $this->comparisons->compareElements($from, $to);
        $editors = ['to' => $this->editor($request, $to, $hasEdits['to'])];
        // Fields whose input HTML comes from `getInputHtml()` get the same ids in each editor, which their labels and
        // scripts would find in A's, so with those on both sides, only B's side is edited.
        $editors['from'] = $sameCurrent || ($editors['to'] && $this->hasLegacyHtml($from, $comparison['from']) && $this->hasLegacyHtml($to, $comparison['to']))
            ? null
            : $this->editor($request, $from, $hasEdits['from']);
        $chip = fn (ElementInterface $element): string => app(PreviewHtml::class)->elementPreviewHtml([$element], showStatus: false);

        return new CpScreenResponse()
            ->title($pair
                ? t('Compare “{from}” with “{to}”', ['from' => $canonicals['from']->getUiLabel(), 'to' => $canonicals['to']->getUiLabel()])
                : t('Compare “{title}”', ['title' => $canonicals['to']->getUiLabel()]))
            ->crumbs([
                ...($pair ? $canonicals['to']->getCrumbs() : $this->crumbs($canonicals['to'])),
                ['label' => t('Compare'), 'current' => true],
            ])
            ->redirectUrl($this->compareUrl($state, $hasEdits, $returnUrl))
            ->inertiaPage('elements/Compare', [
                ...array_map(fn (string $key): array => [
                    ...$this->side($key, $sides[$key], $state, $versions[$key], $hasEdits, $pair, $returnUrl),
                    'ui' => $comparison[$key],
                    'editor' => $editors[$key],
                ], ['from' => 'from', 'to' => 'to']),
                // The sides that badge their changed fields.
                'badged' => $comparison['badged'],
                'counterparts' => $comparison['counterparts'],
                // One element in two sites, whose fields are compared as translations, rather than by their values.
                'translations' => ! $pair && $from->siteId !== $to->siteId,
                'rows' => array_map(
                    fn (FieldComparisonRow $row): array => $row->toArray(),
                    $comparison['rows'],
                ),
                // Where closing the screen goes: back to the element, or for two, where they were chosen.
                'closeUrl' => $pair ? ($returnUrl ?? $to->getCpEditUrl()) : $to->getCpEditUrl(),
                // The heading's chips, as the deletion blocker modal shows them: A's only for two elements.
                'elementChip' => $chip($canonicals['to']),
                'fromElementChip' => $pair ? $chip($canonicals['from']) : null,
                // The same comparison the other way around.
                'reverseUrl' => $state['from'] !== $state['to']
                    ? $this->compareUrl(['from' => $state['to'], 'to' => $state['from']], ['from' => $hasEdits['to'], 'to' => $hasEdits['from']], $returnUrl)
                    : null,
            ]);
    }

    /**
     * The edit screen’s payload for one side, when the user can edit it. The current element as it’s saved can’t be,
     * while the user has unsaved edits to it: saving would go into those edits, as it does on the edit screen, which
     * shows them in its place.
     *
     * @return array<string, mixed>|null
     */
    private function editor(ElementRequest $request, ElementInterface $element, bool $hasEdits): ?array
    {
        if (
            $element->getIsRevision() ||
            ($hasEdits && $element->getIsCanonical()) ||
            ! $request->craftUser()->can('save', $element->isProvisionalDraft ? $element->getCanonical(true) : $element)
        ) {
            return null;
        }

        $viewModelClass = $element::editViewModelClass();
        $viewModel = new $viewModelClass($element, $request);

        event($event = new ElementEditorPayloadResolving($element, $viewModel->toArray(), 'main-form'));

        return $event->data;
    }

    /**
     * Whether an element's form can show a field whose input HTML is rendered the legacy way: one its layout has,
     * whether or not a condition shows it yet, as only legacy fields have `getInputHtml()`, or one nested in the form.
     */
    private function hasLegacyHtml(ElementInterface $element, UiPayload $form): bool
    {
        return array_any(
            $element->getFieldLayout()?->getCustomFields() ?? [],
            fn (FieldInterface $field): bool => method_exists($field, 'getInputHtml'),
        ) || $this->hasLegacyNodes($form->nodes);
    }

    /**
     * Whether any of the nodes, or those nested in them, is a field whose input HTML is rendered the legacy way.
     *
     * @param  list<NodePayload>  $nodes
     */
    private function hasLegacyNodes(array $nodes): bool
    {
        return array_any($nodes, fn ($node) => str_starts_with($node->control->component ?? '', 'craft-legacy:') ||
        $this->hasLegacyNodes($node->children ?? []) ||
        array_any($node->control->uis ?? [], fn (NestedUiPayload $ui): bool => $this->hasLegacyNodes($ui->nodes)));
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
                $this->authorizeSite($siteId);
            }

            $from = $this->version($request, $canonical, $siteId, is_string($version) ? $version : 'current');
        }

        abort_unless($request->craftUser()->can('view', $from), 403, 'User not authorized to view this element.');

        return $from;
    }

    /**
     * The other element the request compares B with, in the site it asks for, or B’s when it’s there, as `fromId`
     * identifies it, or the version of it the request asks for.
     */
    private function otherElement(ElementRequest $request, int $id, ElementInterface $to): ElementInterface
    {
        $elementType = Elements::getElementTypeById($id) ?? abort(404, 'The element to compare with doesn’t exist.');
        $siteId = $elementType::isLocalized() ? $request->integer('fromSiteId') ?: null : null;

        if ($siteId !== null) {
            $this->authorizeSite($siteId);
        }

        $element = $elementType::find()
            ->id($id)
            ->siteId($siteId ?? ($elementType::isLocalized() ? $this->sites->getEditableSiteIds()->all() : '*'))
            ->preferSites([$to->siteId])
            ->unique()
            ->drafts(null)
            ->provisionalDrafts(null)
            ->revisions(null)
            ->status(null)
            ->one();

        abort_if($element === null || $element->getIsUnpublishedDraft(), 404, 'The element to compare with doesn’t exist.');

        $version = $request->query('from');

        if (is_string($version) || $element->getIsCanonical()) {
            $element = $this->version($request, $element->getCanonical(true), $element->siteId, is_string($version) ? $version : 'current');
        }

        abort_unless($request->craftUser()->can('view', $element), 403, 'User not authorized to view this element.');

        return $element;
    }

    /**
     * A version of an element in a site, as `from` identifies it. The current element is the user’s unsaved edits to
     * it, when they have any, unless the request asks for it as it’s saved, with `fromEdits=0`.
     */
    private function version(ElementRequest $request, ElementInterface $canonical, int $siteId, string $version): ElementInterface
    {
        [$type, $id] = array_pad(explode('-', $version, 2), 2, null);
        $query = $canonical::find()->siteId($siteId)->status(null);

        $element = match ($type) {
            'draft' => $query->draftId((int) $id)->one(),
            'revision' => $query->revisionId((int) $id)->trashed(null)->one(),
            // `edits` is what the user's unsaved edits were once called, and still means the same.
            'current', 'edits' => ($request->query('fromEdits') !== '0' ? $this->edits($request, $canonical, $siteId) : null)
                ?? $query->id($canonical->id)->one(),
            default => abort(400, "Invalid version: $version"),
        };

        abort_if($element === null || $element->getCanonicalId() !== $canonical->id, 404, 'The version to compare with doesn’t exist.');

        return $element;
    }

    private function authorizeSite(int $siteId): void
    {
        abort_unless(in_array($siteId, $this->sites->getEditableSiteIds()->all(), true), 403, 'User not authorized to edit content for this site.');
    }

    /**
     * Where the screen was opened from, which closing it goes back to, when that’s a control panel page.
     */
    private function returnUrl(ElementRequest $request): ?string
    {
        $returnUrl = $request->query('returnUrl');
        // Without the query string the control panel's URLs carry, such as the site's.
        $cpUrl = rtrim(strtok(Url::cpUrl(), '?'), '/');

        return is_string($returnUrl) && preg_match('/^'.preg_quote($cpUrl, '/').'(?:[\/?#]|$)/', $returnUrl)
            ? $returnUrl
            : null;
    }

    /**
     * What the screen says about one side of the comparison, with menus that switch it to another version or site and
     * keep the other side as it is.
     *
     * @param  'from'|'to'  $key
     * @param  array{from: array{id: int|null, version: string, siteId: int|null}, to: array{id: int|null, version: string, siteId: int|null}}  $state
     * @param  list<array{key: string, label: string, group: string, badge?: array{label: string, fill: string, icon: string}}>  $versions
     * @param  array{from: bool, to: bool}  $hasEdits
     * @return array{label: string, edited: bool, siteName: string|null, versionMenu: list<array<string, mixed>>, siteMenu: list<array<string, mixed>>}
     */
    private function side(string $key, ElementInterface $element, array $state, array $versions, array $hasEdits, bool $pair, ?string $returnUrl): array
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
                    'href' => $this->compareUrl(array_replace_recursive($state, [$key => $change]), $hasEdits, $returnUrl),
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

        // As in the editor, the user’s unsaved edits are the current version, marked “Edited”. Without versions to
        // tell apart, the side is the element.
        $version = match (true) {
            $versions === [] => null,
            $element->isProvisionalDraft => t('Current'),
            default => $this->comparisons->label($element),
        };

        return [
            // Of two elements, each side is its element, and the version when it isn't the current one.
            'label' => match (true) {
                $version === null => $element->getUiLabel(),
                ! $pair => $version,
                $element->getIsDraft() && ! $element->isProvisionalDraft, $element->getIsRevision() => t('{element}: {version}', [
                    'element' => $element->getUiLabel(),
                    'version' => $version,
                ]),
                default => $element->getUiLabel(),
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
     * The URL of the Compare screen with both sides as given. Each side is the current element with the user’s
     * unsaved edits unless it says otherwise, so the current element as it’s saved does when there are any.
     *
     * @param  array{from: array{id: int|null, version: string, siteId: int|null}, to: array{id: int|null, version: string, siteId: int|null}}  $state
     * @param  array{from: bool, to: bool}  $hasEdits
     */
    private function compareUrl(array $state, array $hasEdits, ?string $returnUrl): string
    {
        [$type, $id] = array_pad(explode('-', $state['to']['version'], 2), 2, null);
        $from = $state['from']['version'];
        $params = [
            'draftId' => $type === 'draft' ? $id : null,
            'revisionId' => $type === 'revision' ? $id : null,
            'edits' => $type === 'current' && $hasEdits['to'] ? 0 : null,
            'siteId' => $state['to']['siteId'],
            'from' => $from === 'edits' ? 'current' : $from,
            'fromEdits' => $from === 'current' && $hasEdits['from'] ? 0 : null,
            'fromSiteId' => $state['from']['siteId'],
            'returnUrl' => $returnUrl,
        ];
        $url = $state['from']['id'] === $state['to']['id']
            ? route('craft.cp.elements.compare', ['id' => $state['to']['id']])
            : route('craft.cp.elements.compare-elements', ['fromId' => $state['from']['id'], 'toId' => $state['to']['id']]);

        return Url::urlWithParams($url, array_filter($params, fn (mixed $value): bool => $value !== null));
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

        // The newest revision is the current entry's, which is listed as “Current” rather than as a revision, as in the
        // Versions panel.
        $revisions = $hasRevisions
            ? $canonical::find()
                ->revisionOf($canonical)
                ->siteId($canonical->siteId)
                ->status(null)
                ->offset(1)
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
