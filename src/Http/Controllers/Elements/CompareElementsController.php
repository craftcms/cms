<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Elements;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Data\FieldComparisonRow;
use CraftCms\Cms\Element\ElementHelper;
use CraftCms\Cms\Element\Events\ElementEditorPayloadResolving;
use CraftCms\Cms\Element\FieldChangeComparisons;
use CraftCms\Cms\Http\Controllers\Elements\Concerns\ElementCrumbs;
use CraftCms\Cms\Http\Requests\ElementRequest;
use CraftCms\Cms\Http\Responses\CpScreenResponse;
use CraftCms\Cms\Site\Sites;
use CraftCms\Cms\Support\Url;
use Symfony\Component\HttpFoundation\Response;

use function CraftCms\Cms\t;

/**
 * The Compare screen, which shows two versions of an entry side by side, field by field: a draft and the current
 * entry, two revisions, or the entry in two sites.
 *
 * The newer version is identified like the editor’s, by `draftId`, `revisionId`, and `siteId`. The one it’s compared
 * with is identified by `from` (`current`, `draft-{draftId}`, or `revision-{revisionId}`) and `fromSiteId`, and
 * defaults to what the newer version was based on.
 *
 * The newer version is edited in place when it can be — the current entry or a draft the user can save, but never a
 * revision — through the same editor payload as its edit page, and saving it comes back here.
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
        $to = $request->element(['id' => $request->route('id')]);

        if ($to instanceof Response) {
            return $to;
        }

        if (! $to || ! $to->id || $to->getIsUnpublishedDraft()) {
            abort(400, 'No element was identified by the request.');
        }

        $canonical = $to->getCanonical(true);
        $from = $this->fromVersion($request, $to, $canonical);
        $versions = $this->versions($request, $canonical, [$from, $to]);
        $siteIds = $this->siteIds($canonical);
        $state = [
            'from' => ['version' => $this->versionKey($from), 'siteId' => $from->siteId],
            'to' => ['version' => $this->versionKey($to), 'siteId' => $to->siteId],
        ];

        $comparison = $this->comparisons->compareElements($from, $to);

        return new CpScreenResponse()
            ->title(t('Compare “{title}”', ['title' => $canonical->getUiLabel()]))
            ->crumbs([
                ...$this->crumbs($canonical),
                ['label' => t('Compare'), 'current' => true],
            ])
            ->redirectUrl($this->compareUrl($request, $state))
            ->inertiaPage('elements/Compare', [
                'from' => [
                    ...$this->side($request, 'from', $from, $state, $versions, $siteIds),
                    'ui' => $comparison['from'],
                ],
                'to' => [
                    ...$this->side($request, 'to', $to, $state, $versions, $siteIds),
                    'ui' => $comparison['to'],
                    'editor' => $this->editor($request, $to),
                ],
                'rows' => array_map(
                    fn (FieldComparisonRow $row): array => $row->toArray(),
                    $comparison['rows'],
                ),
                'editUrl' => $to->getCpEditUrl(),
                // The same comparison the other way around, which edits the other version, when it can be.
                'reverseUrl' => $state['from'] !== $state['to']
                    ? $this->compareUrl($request, ['from' => $state['to'], 'to' => $state['from']])
                    : null,
            ]);
    }

    /**
     * The edit screen’s payload for the newer version, when the user can edit it.
     *
     * @return array<string, mixed>|null
     */
    private function editor(ElementRequest $request, ElementInterface $to): ?array
    {
        if ($to->getIsRevision() || ! $request->craftUser()->can('save', $to->isProvisionalDraft ? $to->getCanonical(true) : $to)) {
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
        $siteId = $request->integer('fromSiteId') ?: $to->siteId;

        if (! is_string($version) && $siteId === $to->siteId) {
            return $this->comparisons->baseOf($to) ?? $to;
        }

        abort_unless(in_array($siteId, $this->sites->getEditableSiteIds()->all(), true), 403, 'User not authorized to edit content for this site.');

        [$type, $id] = array_pad(explode('-', is_string($version) ? $version : 'current', 2), 2, null);
        $query = $canonical::find()->siteId($siteId)->status(null);

        $from = match ($type) {
            'draft' => $query->draftId((int) $id)->one(),
            'revision' => $query->revisionId((int) $id)->trashed(null)->one(),
            'current' => $query->id($canonical->id)->one(),
            default => abort(400, "Invalid version: $version"),
        };

        abort_if($from === null || $from->getCanonicalId() !== $canonical->id, 404, 'The version to compare with doesn’t exist.');
        abort_unless($request->craftUser()->can('view', $from), 403, 'User not authorized to view this element.');

        return $from;
    }

    /**
     * What the screen says about one side of the comparison, with menus that switch it to another version or site and
     * keep the other side as it is.
     *
     * @param  'from'|'to'  $key
     * @param  array{from: array{version: string, siteId: int}, to: array{version: string, siteId: int}}  $state
     * @param  list<array{key: string, label: string, group: string}>  $versions
     * @param  list<int>  $siteIds
     * @return array{label: string, siteName: string|null, versionMenu: list<array<string, mixed>>, siteMenu: list<array<string, mixed>>}
     */
    private function side(ElementRequest $request, string $key, ElementInterface $element, array $state, array $versions, array $siteIds): array
    {
        $link = fn (string $label, array $change, bool $selected): array => [
            'type' => 'link',
            'label' => $label,
            'href' => $this->compareUrl($request, array_replace_recursive($state, [$key => $change])),
            'selected' => $selected,
        ];
        $groups = ['current' => null, 'drafts' => t('Drafts'), 'revisions' => t('Revisions')];
        $versionMenu = [];

        foreach ($groups as $group => $heading) {
            $items = array_map(
                fn (array $version): array => $link($version['label'], ['version' => $version['key']], $version['key'] === $state[$key]['version']),
                array_values(array_filter($versions, fn (array $version): bool => $version['group'] === $group)),
            );

            if ($items !== []) {
                $versionMenu[] = ['type' => 'group', 'heading' => $heading, 'items' => $items];
            }
        }

        return [
            // As in the editor, the user’s unsaved edits are the current version, marked “Edited”.
            'label' => $element->isProvisionalDraft ? t('Current') : $this->comparisons->label($element),
            'siteName' => $siteIds !== [] ? $element->getSite()->getName() : null,
            'versionMenu' => $versionMenu,
            'siteMenu' => array_map(
                fn (int $siteId): array => $link($this->sites->getSiteById($siteId)->getName(), ['siteId' => $siteId], $siteId === $element->siteId),
                $siteIds,
            ),
        ];
    }

    /**
     * The URL of the Compare screen with both sides as given.
     *
     * @param  array{from: array{version: string, siteId: int}, to: array{version: string, siteId: int}}  $state
     */
    private function compareUrl(ElementRequest $request, array $state): string
    {
        [$type, $id] = array_pad(explode('-', $state['to']['version'], 2), 2, null);
        $params = [
            'draftId' => $type === 'draft' ? $id : null,
            'revisionId' => $type === 'revision' ? $id : null,
            'siteId' => $state['to']['siteId'],
            'from' => $state['from']['version'],
            'fromSiteId' => $state['from']['siteId'],
        ];

        return Url::urlWithParams($request->url(), array_filter($params, fn (mixed $value): bool => $value !== null));
    }

    /**
     * Identifies a version in the pickers: `current`, `draft-{draftId}`, or `revision-{revisionId}`. The user’s
     * provisional draft is their edits to the current entry, which the editor loads in its place.
     */
    private function versionKey(ElementInterface $element): string
    {
        return match (true) {
            $element->getIsDraft() && ! $element->isProvisionalDraft => "draft-$element->draftId",
            $element->getIsRevision() => "revision-$element->revisionId",
            default => 'current',
        };
    }

    /**
     * The versions either side can show: the current entry, its drafts, and its recent revisions, along with any
     * older revision that’s being compared.
     *
     * @param  list<ElementInterface>  $selected
     * @return list<array{key: string, label: string, group: string}>
     */
    private function versions(ElementRequest $request, ElementInterface $canonical, array $selected): array
    {
        $user = $request->craftUser();

        $drafts = $canonical::find()
            ->draftOf($canonical)
            ->siteId($canonical->siteId)
            ->status(null)
            ->orderByDesc('dateUpdated')
            ->get()
            ->filter(fn (ElementInterface $draft): bool => $user->can('view', $draft));

        $revisions = $canonical->hasRevisions()
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
            ['key' => 'current', 'label' => t('Current'), 'group' => 'current'],
            ...$drafts->map(fn (ElementInterface $draft): array => [
                'key' => $this->versionKey($draft),
                'label' => $draft->draftName ?: t('Draft'),
                'group' => 'drafts',
            ])->values()->all(),
            ...$revisions->map(fn (ElementInterface $revision): array => [
                'key' => $this->versionKey($revision),
                'label' => $revision->getRevisionLabel(),
                'group' => 'revisions',
            ])->values()->all(),
        ];
    }

    /**
     * The sites either side can show: the ones the entry is in that the user can edit, or none for a single site.
     *
     * @return list<int>
     */
    private function siteIds(ElementInterface $canonical): array
    {
        $editableSiteIds = $this->sites->getEditableSiteIds()->all();
        $siteIds = array_values(array_filter(
            array_column(ElementHelper::supportedSitesForElement($canonical), 'siteId'),
            fn (int $siteId): bool => in_array($siteId, $editableSiteIds, true),
        ));

        return count($siteIds) > 1 ? $siteIds : [];
    }
}
