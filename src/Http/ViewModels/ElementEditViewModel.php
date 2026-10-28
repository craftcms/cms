<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\ViewModels;

use CraftCms\Cms\Cms;
use CraftCms\Cms\Cp\Data\ActionItem;
use CraftCms\Cms\Cp\Html\ContentHtml;
use CraftCms\Cms\Cp\Html\StatusHtml;
use CraftCms\Cms\Cp\Icons;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Contracts\NestedElementInterface;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Element\ElementEditorActions;
use CraftCms\Cms\Element\ElementHelper;
use CraftCms\Cms\Element\Enums\ElementActionContext;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\FieldLayout\FieldLayoutCompiler;
use CraftCms\Cms\Http\Controllers\Elements\Concerns\EditsElement;
use CraftCms\Cms\Http\Controllers\Elements\Concerns\ElementCrumbs;
use CraftCms\Cms\Http\Requests\ElementRequest;
use CraftCms\Cms\Release\Data\AttachedReleaseData;
use CraftCms\Cms\Release\Data\ReleaseSummaryData;
use CraftCms\Cms\Release\Enums\ReleaseStatus;
use CraftCms\Cms\Release\Models\Release;
use CraftCms\Cms\Release\Models\ReleaseItem;
use CraftCms\Cms\Release\Releases;
use CraftCms\Cms\RouteToken\RouteTokens;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\DeltaRegistry;
use CraftCms\Cms\Support\Facades\I18N;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Facades\Workflows;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Support\Url;
use CraftCms\Cms\Translation\Locale;
use CraftCms\Cms\Ui\Enums\ControlMode;
use CraftCms\Cms\Ui\UiContext;
use CraftCms\Cms\Ui\UiPayload;
use CraftCms\Cms\Ui\UiResolver;
use CraftCms\Cms\Workflow\Contracts\WorkflowableInterface;
use CraftCms\Cms\Workflow\Data\WorkflowDraftReviewData;
use CraftCms\Cms\Workflow\Data\WorkflowReviewData;
use CraftCms\Cms\Workflow\Enums\WorkflowStatus;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Tpetry\QueryExpressions\Operator\Comparison\IsNull;

use function CraftCms\Cms\currentUser;
use function CraftCms\Cms\t;

/**
 * The shared Inertia payload for an element edit screen.
 *
 * The element's field layout is compiled to a {@see UiPayload} through the
 * same {@see FieldLayoutCompiler} the legacy editor and the slideouts use, so
 * both renderers stay in sync; everything page-shaped (titles, crumbs, the save
 * target, and the sidebar islands) lives here. Tabs belong to the UI renderer.
 *
 * Element-type view models extend this with their own payload keys (public
 * methods) and supply their save route via {@see saveUrl()} — e.g. entries
 * post to the entry store action, assets to their own.
 *
 * Public methods are payload keys (see {@see ViewModel}); shared intermediates
 * (the compiled UI) are memoized privately since payload methods may be
 * invoked in any order.
 *
 * @since 6.0.0
 */
class ElementEditViewModel extends ViewModel
{
    /**
     * How many revisions {@see self::contextMenu()} lists — a most-recent
     * window onto a longer list, which links out to the full revisions index
     * when there are more. Drafts are listed in full: they're the ones still
     * being worked on, and there's no drafts index to link out to.
     */
    private const int MAX_CONTEXT_MENU_REVISIONS = 5;

    use EditsElement;

    // Aliased so the payload key below can keep the `crumbs` name without
    // shadowing the trait's element-crumb builder.
    use ElementCrumbs {
        crumbs as protected elementCrumbs;
    }

    private ?UiPayload $ui = null;

    private ?string $previewToken = null;

    /** @var list<array<string, mixed>>|null */
    private ?array $previewTargetsCache = null;

    /** @var array<string, mixed>|null */
    private ?array $previewTokenParams = null;

    private bool $uiResolved = false;

    private ?UiPayload $sidebarUi = null;

    private bool $sidebarUiResolved = false;

    private ?Release $attachedRelease = null;

    private bool $attachedReleaseResolved = false;

    public function __construct(
        protected readonly ElementInterface $element,
        ElementRequest $request,
        protected readonly bool $canSave = true,
        protected readonly bool $mergedCanonicalChanges = false,
    ) {
        $this->request = $request;
    }

    /**
     * The element type's own store action. The UI payload submits ordinary
     * nested input names, so the existing save controllers read it without a
     * translation layer.
     */
    protected function elementSaveUrl(): string
    {
        return Url::actionUrl('elements/save');
    }

    /** @return array<string, int|string|null> */
    public function saveParams(): array
    {
        return [
            'elementType' => $this->element::class,
            'elementId' => $this->element->getIsDraft() || $this->element->getIsRevision()
                ? $this->element->getCanonicalId()
                : $this->element->id,
            'siteId' => $this->element->siteId,
        ];
    }

    /**
     * Adopts an already-compiled field layout instead of compiling one.
     *
     * Autosave compiles the layout for its own response before it rebuilds this
     * payload; handing that compilation over is what keeps a keystroke from
     * paying for the same work twice. Takes an argument, so {@see ViewModel}
     * leaves it out of the payload.
     */
    public function withUi(?UiPayload $ui): static
    {
        $this->ui = $ui;
        $this->uiResolved = true;

        return $this;
    }

    /** The element's own control panel edit page, which a slideout links to as "Open in a new tab". */
    public function cpEditUrl(): ?string
    {
        return $this->element->getCpEditUrl();
    }

    /**
     * Where the Save button lands: back where the editor was opened from, as on
     * the legacy screen. Saving a named draft stays on the draft.
     *
     * Encrypted, because the save controllers decrypt whatever `redirect` a
     * request posts rather than trusting it.
     */
    public function redirectUrl(): string
    {
        $isNamedDraft = $this->element->getIsDraft()
            && ! $this->element->getIsUnpublishedDraft()
            && ! $this->element->isProvisionalDraft;

        return Crypt::encrypt($isNamedDraft ? '{cpEditUrl}' : $this->postEditRedirectUrl($this->element));
    }

    /** Where the edit form posts when there are no provisional changes to apply. */
    public function saveUrl(): string
    {
        return $this->elementSaveUrl();
    }

    /**
     * Where the edit form posts once provisional changes exist.
     *
     * The client picks between this and {@see saveUrl()} at submit time rather
     * than relying on the state at page load: autosave can create a provisional
     * draft partway through editing a canonical element, and from that point on
     * saving means applying the draft, not saving the element under it.
     */
    public function applyDraftUrl(): string
    {
        return Url::actionUrl('elements/apply-draft');
    }

    /**
     * Where unsaved changes are autosaved. Posting here with no draft yet
     * creates the provisional draft; afterwards it updates it in place.
     */
    public function autosaveUrl(): string
    {
        return Url::actionUrl('elements/save-draft');
    }

    /** Where a provisional draft is discarded, reverting to the canonical element. */
    public function discardDraftUrl(): string
    {
        return Url::actionUrl('elements/delete-draft');
    }

    /**
     * Where a nested element's changes are saved when they belong in a draft of
     * its owner rather than the canonical owner it was queried through.
     */
    public function saveForDerivativeUrl(): string
    {
        return Url::actionUrl('elements/save-nested-element-for-derivative');
    }

    /**
     * The field and owner a nested element is being edited through.
     *
     * Every request the editor sends carries these, so the server resolves the
     * element against that owner (an owner draft included) rather than its
     * primary owner.
     *
     * @return array{fieldId: int|null, ownerId: int}|null
     */
    public function nestedContext(): ?array
    {
        if (! $this->element instanceof NestedElementInterface || ! $this->element->getOwnerId()) {
            return null;
        }

        return [
            'fieldId' => $this->element->getField()?->id,
            'ownerId' => $this->element->getOwnerId(),
        ];
    }

    /**
     * Whether this is a brand-new element being filled in for the first time.
     * Posted back with each save so it propagates to all of its sites.
     */
    public function fresh(): bool
    {
        return $this->request->boolean('fresh') && $this->element->getIsUnpublishedDraft();
    }

    /**
     * @return array{
     *     primary: array{label: string, actionUrl: string|null, params: array<string, mixed>, redirect: null, panelId: string|null},
     *     menu: list<array{label: string, actionUrl: string|null, params: array<string, mixed>, redirect: string|null, shortcut: bool, shift: bool}>,
     *     buttons: list<array<string, mixed>>,
     * }
     */
    public function editorActions(): array
    {
        $actions = app(ElementEditorActions::class)->for(
            element: $this->element,
            canSave: $this->canSave,
            review: $this->workflowReview(),
            primaryLabel: $this->primaryActionLabel(),
            isNew: $this->request->boolean('fresh'),
        );

        $release = $this->attachedRelease();
        if ($release !== null) {
            $actions['primary']['label'] = t('Save draft');
            $actions['primary']['actionUrl'] = $this->autosaveUrl();
            $actions['primary']['panelId'] = null;

            if (! $release->status->isMutable()) {
                $actions['buttons'] = array_map(
                    fn (array $action): array => $action['actionUrl'] === $this->applyDraftUrl()
                        ? [
                            ...$action,
                            'disabled' => true,
                            'disabledReason' => t('Cancel the release schedule before applying this draft.'),
                        ]
                        : $action,
                    $actions['buttons'],
                );
            }
        }

        return $actions;
    }

    /**
     * Buttons the element type and plugins add to the footer, apart from the save controls.
     *
     * @return list<array<string, mixed>>
     */
    public function additionalButtons(): array
    {
        return $this->element->additionalButtonDescriptors();
    }

    protected function primaryActionLabel(): ?string
    {
        return null;
    }

    /** @return array{convertedToDraft: bool, current: WorkflowReviewData|null, draftReviews: list<WorkflowDraftReviewData>} */
    public function workflow(): array
    {
        return [
            'convertedToDraft' => $this->element->draftId !== null
                && (int) session()->get('workflowDraftCreated') === $this->element->draftId,
            'current' => $this->workflowReview(),
            'draftReviews' => [],
        ];
    }

    public function release(): ?AttachedReleaseData
    {
        if (! $this->element->getIsDraft() || $this->element->isProvisionalDraft) {
            return null;
        }

        $release = $this->attachedRelease();
        $actor = currentUser();

        if ($release === null || $actor === null) {
            return null;
        }

        return new AttachedReleaseData(
            id: $release->id,
            name: $release->name,
            status: $release->status,
            targetDate: $release->targetDate,
            canManage: $actor->can('manage', $release),
            draftId: (int) $this->element->draftId,
            siteId: $this->element->siteId,
        );
    }

    /** @return list<ReleaseSummaryData> */
    public function releaseOptions(): array
    {
        if (! $this->canAddToRelease()) {
            return [];
        }

        $actor = currentUser();
        if ($actor === null) {
            return [];
        }

        $canonicalId = $this->element->getCanonicalId();
        $releaseIdsWithCanonical = is_int($canonicalId) && $canonicalId > 0
            ? ReleaseItem::query()->where('canonicalId', $canonicalId)->pluck('releaseId')->all()
            : [];

        return Release::query()
            ->whereIn('status', [ReleaseStatus::Draft, ReleaseStatus::Failed])
            ->orderBy(new IsNull('targetDate'))
            ->orderBy('targetDate')
            ->orderBy('name')
            ->get()
            ->filter(fn (Release $release): bool => ! in_array($release->id, $releaseIdsWithCanonical, true)
                && $actor->can('attach', [$release, $this->element]))
            ->map(fn (Release $release): ReleaseSummaryData => new ReleaseSummaryData(
                id: $release->id,
                name: $release->name,
                status: $release->status,
                targetDate: $release->targetDate,
                publishedAt: $release->publishedAt,
                itemCount: null,
            ))
            ->values()
            ->all();
    }

    public function canCreateRelease(): bool
    {
        return (currentUser()?->can('manageReleases') ?? false)
            && $this->canAddToRelease();
    }

    public function canAddToRelease(): bool
    {
        $actor = currentUser();
        if (
            ! Edition::get()->supportsReleases()
            || ! $this->canSave
            || ! $this->element::hasDrafts()
            || ! $this->element->getIsDraft()
            || $this->attachedRelease() !== null
            || $actor === null
            || ! $this->releaseApprovalReady()
        ) {
            return false;
        }

        $prospectiveRelease = new Release;
        $prospectiveRelease->status = ReleaseStatus::Draft;

        return $actor->can('attach', [$prospectiveRelease, $this->element]);
    }

    private function releaseApprovalReady(): bool
    {
        if (! Workflows::requiresApproval($this->element)) {
            return true;
        }

        if ($this->element->isProvisionalDraft) {
            return false;
        }

        return Workflows::latestRun($this->element)?->status === WorkflowStatus::Approved
            && ! ElementHelper::isOutdated($this->element);
    }

    public function releaseTimeZone(): string
    {
        return Cms::timezone();
    }

    public function draftIsSaved(): bool
    {
        if (! $this->element->getIsDraft() || $this->element->isProvisionalDraft || ! $this->element->draftId) {
            return false;
        }

        return (bool) DB::table(Table::DRAFTS)
            ->where('id', $this->element->draftId)
            ->value('saved');
    }

    protected function workflowReview(): ?WorkflowReviewData
    {
        return once(function (): ?WorkflowReviewData {
            $user = currentUser();

            if (
                $user === null ||
                ! $this->element instanceof WorkflowableInterface ||
                ! $this->element->getIsDraft() ||
                $this->element->isProvisionalDraft
            ) {
                return null;
            }

            return Workflows::reviewData($this->element, $user);
        });
    }

    /** The element type's lowercase display name, for user-facing messages. */
    public function elementDisplayName(): string
    {
        return $this->element::lowerDisplayName();
    }

    /**
     * Where the screen polls for other people working on this element.
     *
     * Revisions are read-only and can't be collaborated on, so they don't poll.
     */
    public function activityUrl(): ?string
    {
        return $this->element->id && ! $this->element->getIsRevision()
            ? Url::actionUrl('elements/recent-activity')
            : null;
    }

    /** Where this editor's activity timeline is loaded on demand. */
    public function activityTimelineUrl(): ?string
    {
        return null;
    }

    /** Where the full activity page can be viewed. */
    public function activityPageUrl(): ?string
    {
        return null;
    }

    /**
     * The element's and its canonical's last-modified stamps at render time.
     * Activity polling compares these against the server's to notice that
     * something changed underneath the person editing.
     *
     * @return array{element: int|null, canonical: int|null}
     */
    public function updatedTimestamps(): array
    {
        return [
            'element' => $this->element->dateUpdated?->getTimestamp(),
            'canonical' => $this->element->getCanonical(true)->dateUpdated?->getTimestamp(),
        ];
    }

    /**
     * The element's preview targets as ready-to-open links.
     *
     * A live element links straight at its URL. Anything not publicly visible
     * yet — a draft, a disabled entry, a future post date — links at
     * `preview/create-token`, which mints a preview token and redirects to the
     * tokenized URL. The legacy editor does that dance in JavaScript; every
     * input is known here, so the link arrives ready to follow.
     *
     * @return list<array{label: string, url: string}>
     */
    public function previewTargets(): array
    {
        $targets = $this->rawPreviewTargets();

        if ($targets === []) {
            return [];
        }

        $isLive = $this->isLive();
        $siteToken = $this->siteToken();
        $tokenParams = $isLive ? [] : $this->previewTokenParams();

        return array_values(array_map(function (array $target) use ($isLive, $siteToken, $tokenParams): array {
            $params = array_filter([
                // Randomized so CDNs don't serve a cached page.
                'x-craft-preview' => $isLive ? null : Crypt::encrypt(Str::random(10)),
                Cms::config()->siteToken => $siteToken,
            ], fn (mixed $value): bool => $value !== null);

            if ($isLive) {
                return [
                    'label' => (string) $target['label'],
                    'url' => Url::url($target['url'], $params),
                ];
            }

            $params[Cms::config()->tokenParam] = $this->previewToken();

            return [
                'label' => (string) $target['label'],
                'url' => Url::actionUrl('preview/create-token', [
                    ...$tokenParams,
                    'redirect' => Url::url($target['url'], $params),
                ]),
            ];
        }, $targets));
    }

    /**
     * What Live Preview needs to load the element's targets in an iframe, or
     * `null` when the element can't be previewed from this screen.
     *
     * The target URLs are untokenized; the client adds the token once it has
     * been created, along with the preview param, which changes on every save
     * so a cached page is never shown.
     *
     * @return array<string, mixed>|null
     */
    public function preview(): ?array
    {
        $targets = $this->rawPreviewTargets();

        if ($targets === [] || ! $this->canPreview()) {
            return null;
        }

        $config = Cms::config();

        return [
            'targets' => array_values(array_map(fn (array $target): array => [
                'label' => (string) $target['label'],
                'url' => (string) $target['url'],
                'refresh' => (bool) ($target['refresh'] ?? true),
            ], $targets)),
            'createTokenUrl' => Url::actionUrl('preview/create-token'),
            'tokenParams' => $this->previewTokenParams(),
            'token' => $this->previewToken(),
            'tokenDuration' => app(RouteTokens::class)->previewTokenDuration(),
            'tokenParam' => $config->tokenParam,
            'paramValue' => Crypt::encrypt(Str::random(10)),
            'siteTokenParam' => $config->siteToken,
            'siteToken' => $this->siteToken(),
            'iframeResizerOptions' => $config->useIframeResizer
                ? (object) $config->previewIframeResizerOptions
                : false,
        ];
    }

    /**
     * Craft 5's rule: a draft can be previewed by whoever can save it, and the
     * current element by whoever can draft it, since previewing it makes one.
     */
    protected function canPreview(): bool
    {
        $element = $this->element;

        if ($this->request->isMobileBrowser(true) || $element->getIsRevision()) {
            return false;
        }

        if ($element->getIsDraft() && ! $element->isProvisionalDraft) {
            return $this->canSave;
        }

        return Gate::check('createDrafts', $element->getCanonical(true));
    }

    /** @return list<array<string, mixed>> */
    private function rawPreviewTargets(): array
    {
        if (! $this->element->id) {
            return [];
        }

        return $this->previewTargetsCache ??= $this->element->getPreviewTargets();
    }

    private function isLive(): bool
    {
        $element = $this->element;

        return ($element->getIsCanonical() || $element->isProvisionalDraft) &&
            ! $element->getIsDraft() &&
            $element->enabled &&
            $element->getEnabledForSite() &&
            $element->getRoute() !== null;
    }

    private function siteToken(): ?string
    {
        return once(fn (): ?string => (app()->isDownForMaintenance() || ! $this->element->getSite()->getEnabled())
            ? Crypt::encrypt((string) $this->element->siteId)
            : null);
    }

    /**
     * One token per screen, shared by the View links and Live Preview, so
     * whichever is used first creates it for both.
     */
    private function previewToken(): string
    {
        return $this->previewToken ??= Str::random(32, extendedChars: true);
    }

    /** @return array<string, mixed> */
    private function previewTokenParams(): array
    {
        return $this->previewTokenParams ??= $this->buildPreviewTokenParams();
    }

    /** @return array<string, mixed> */
    private function buildPreviewTokenParams(): array
    {
        $element = $this->element;

        return array_filter([
            'elementType' => $element::class,
            'canonicalId' => $element->getCanonicalId(),
            'siteId' => $element->siteId,
            'revisionId' => $element->revisionId,
            'draftId' => $element->isProvisionalDraft ? null : $element->draftId,
            'previewToken' => Crypt::encrypt($this->previewToken()),
        ], fn (mixed $value): bool => $value !== null);
    }

    /**
     * The element's action menu, as behavior descriptors the client dispatches.
     *
     * "View in a new tab" is dropped once preview targets exist, since the
     * preview control already covers it, and "Edit" never appears — this screen
     * is the edit screen.
     *
     * @return list<ActionItem|array<string, mixed>>
     */
    public function actionMenu(): array
    {
        if (! $this->element->id) {
            return [];
        }

        $hidesView = $this->rawPreviewTargets() !== [];

        return array_values(array_filter(
            $this->element->actionMenuDescriptors(ElementActionContext::Editor),
            fn (array $item): bool => ! (
                $hidesView && ($item['behavior']['type'] ?? null) === 'link'
            ),
        ));
    }

    /**
     * The drafts and revisions the editor can switch between, listed in the
     * Revisions tab and folded into {@see self::revisionCrumb()}.
     *
     * Groups are flattened into a single item list — headings become `heading`
     * entries the client renders as non-interactive rows — because the action
     * menu's item contract has no nested-group shape.
     *
     * @return array<string, mixed>|null
     */
    public function contextMenu(): ?array
    {
        return once(fn (): ?array => $this->buildContextMenu());
    }

    /** @return array<string, mixed>|null */
    private function buildContextMenu(): ?array
    {
        $element = $this->element->isProvisionalDraft
            ? $this->element->getCanonical(true)
            : $this->element;

        if (! $element->id || $element->getIsUnpublishedDraft()) {
            return null;
        }

        $drafts = $element::find()
            ->draftOf($element)
            ->siteId($element->siteId)
            ->status(null)
            ->orderByDesc('dateUpdated')
            ->with(['draftCreator'])
            ->get()
            ->filter(fn (ElementInterface $draft): bool => $this->request->craftUser()->can('view', $draft))
            ->all();

        $revisions = $this->recentRevisions($element);

        if ($drafts === [] && $revisions->isEmpty()) {
            return null;
        }

        $formatter = I18N::getFormatter();
        $baseParams = Arr::except($this->request->query(), ['draftId', 'revisionId', 'siteId', 'fresh']);
        $cpEditUrl = Url::cpUrl($element->getCpEditUrl(), ['draftId' => null, 'revisionId' => null]);
        $isDraft = $this->element->getIsDraft() && ! $this->element->isProvisionalDraft;
        $isRevision = $this->element->getIsRevision();

        $currentRevision = $element->getCurrentRevision();
        $currentCreator = $currentRevision?->getRevisionCreator();
        $currentTimestamp = $formatter->asTimestamp(
            $currentRevision->dateCreated ?? $element->dateUpdated,
            Locale::LENGTH_SHORT,
            true,
        );

        $items = [[
            'type' => 'link',
            'label' => t('Current'),
            'description' => $currentCreator
                ? t('Saved {timestamp} by {creator}', ['timestamp' => $currentTimestamp, 'creator' => $currentCreator->name])
                : t('Last saved {timestamp}', ['timestamp' => $currentTimestamp]),
            'href' => $cpEditUrl,
            'selected' => ! $isDraft && ! $isRevision,
        ]];

        if ($drafts !== []) {
            $items[] = ['type' => 'heading', 'label' => t('Drafts')];

            foreach ($drafts as $draft) {
                $creator = $draft->getDraftCreator();
                $timestamp = $formatter->asTimestamp($draft->dateUpdated, Locale::LENGTH_SHORT, true);

                $items[] = [
                    'type' => 'link',
                    'label' => (string) $draft->draftName,
                    'description' => $creator
                        ? t('Saved {timestamp} by {creator}', ['timestamp' => $timestamp, 'creator' => $creator->name])
                        : t('Last saved {timestamp}', ['timestamp' => $timestamp]),
                    'href' => Url::urlWithParams($cpEditUrl, [...$baseParams, 'draftId' => $draft->draftId]),
                    'selected' => $draft->id === $this->element->id,
                ];
            }
        }

        if ($revisions->isNotEmpty()) {
            $items[] = ['type' => 'heading', 'label' => t('Recent Revisions')];

            foreach ($revisions as $revision) {
                $creator = $revision->getRevisionCreator();
                $timestamp = $formatter->asTimestamp($revision->dateCreated, Locale::LENGTH_SHORT, true);

                $items[] = [
                    'type' => 'link',
                    'label' => (string) $revision->getRevisionLabel(),
                    'description' => $creator
                        ? t('Saved {timestamp} by {creator}', ['timestamp' => $timestamp, 'creator' => $creator->name])
                        : t('Saved {timestamp}', ['timestamp' => $timestamp]),
                    'href' => Url::urlWithParams($cpEditUrl, [...$baseParams, 'revisionId' => $revision->revisionId]),
                    'selected' => $revision->id === $this->element->id,
                ];
            }
        }

        if (($revisionsUrl = $element->getCpRevisionsUrl()) !== null && $this->hasMoreRevisions($element)) {
            $items[] = ['type' => 'hr'];
            $items[] = [
                'type' => 'link',
                'label' => t('View all revisions'),
                'href' => Url::cpUrl($revisionsUrl),
                'selected' => false,
            ];
        }

        return [
            'label' => $this->editElementTitles($this->element)[1],
            'items' => $items,
        ];
    }

    /** @return Collection<int, ElementInterface> */
    private function recentRevisions(ElementInterface $element): Collection
    {
        if (! $element->hasRevisions() || Cms::config()->maxRevisions === 1) {
            return collect();
        }

        $revisions = $this->revisionQuery($element)->get();

        // A revision being viewed is always listed, even when it has aged out
        // of the recent window.
        if (
            $this->element->getIsRevision() &&
            $revisions->doesntContain(fn (ElementInterface $revision): bool => $revision->id === $this->element->id)
        ) {
            $revisions->push($this->element);
        }

        return $revisions;
    }

    private function hasMoreRevisions(ElementInterface $element): bool
    {
        if (! $element->hasRevisions() || Cms::config()->maxRevisions === 1) {
            return false;
        }

        return ($this->revisionQuery($element)->getCountForPagination() - 1) > 0;
    }

    private function revisionQuery(ElementInterface $element): ElementQueryInterface
    {
        $maxRevisions = Cms::config()->maxRevisions;

        return $element::find()
            ->revisionOf($element)
            ->siteId($element->siteId)
            ->status(null)
            ->offset(1)
            ->limit($maxRevisions
                ? min($maxRevisions - 1, self::MAX_CONTEXT_MENU_REVISIONS)
                : self::MAX_CONTEXT_MENU_REVISIONS)
            ->orderByDesc('dateCreated')
            ->with(['revisionCreator']);
    }

    public function isProvisionalDraft(): bool
    {
        return (bool) $this->element->isProvisionalDraft;
    }

    public function draftId(): ?int
    {
        return $this->element->draftId;
    }

    /**
     * Autosaving means writing a provisional draft, so an element type without
     * drafts — an asset, a user — saves only when the Save button is pressed.
     */
    public function canAutosave(): bool
    {
        return $this->canSave
            && $this->element::hasDrafts()
            && ! $this->element->getIsRevision();
    }

    /**
     * The notice shown above the editor when the screen isn't showing the
     * canonical element as it stands.
     */
    public function notice(): ?string
    {
        return match (true) {
            $this->element->isProvisionalDraft => t('Showing your unsaved changes.'),
            $this->element->getIsRevision() => t('You’re viewing a revision. None of the {type}’s fields are editable.', [
                'type' => $this->element::lowerDisplayName(),
            ]),
            default => null,
        };
    }

    /**
     * Shown when an outdated draft picked up newer canonical changes on the way
     * in, so the author knows the content shifted under them.
     */
    public function mergeNotice(): ?string
    {
        return $this->mergedCanonicalChanges
            ? t('Recent changes to the Current revision have been merged into this draft.')
            : null;
    }

    /** Whether the notice offers to throw the provisional changes away. */
    public function canDiscardDraft(): bool
    {
        return (bool) $this->element->isProvisionalDraft;
    }

    public function elementId(): ?int
    {
        return $this->element->id;
    }

    public function canonicalId(): ?int
    {
        return $this->element->getCanonical(true)->id;
    }

    /** @return class-string<ElementInterface> */
    public function elementType(): string
    {
        return $this->element::class;
    }

    /** The element type's reference handle, which its reference tags start with. */
    public function refHandle(): ?string
    {
        return $this->element::refHandle();
    }

    /** The element's front-end URL — the canonical element's, for a draft or revision. */
    public function elementUrl(): ?string
    {
        return $this->element->getCanonical(true)->getUrl();
    }

    public function siteId(): ?int
    {
        return $this->element->siteId;
    }

    public function fieldLayoutId(): ?int
    {
        return $this->element->fieldLayoutId;
    }

    public function title(): string
    {
        return $this->editElementTitles($this->element)[1];
    }

    public function docTitle(): string
    {
        return $this->editElementTitles($this->element)[0];
    }

    /**
     * The screen's breadcrumbs. On a multi-site install a localized element
     * leads with a site crumb whose menu switches sites, mirroring the legacy
     * editor.
     *
     * @return list<ActionItem|array<string, mixed>>
     */
    public function crumbs(): array
    {
        $revisionCrumb = $this->revisionCrumb();

        return array_values(array_filter([
            $this->siteCrumb(),
            ...$this->elementCrumbs($this->element, hyperlink: $revisionCrumb !== null),
            $revisionCrumb,
        ]));
    }

    /**
     * A crumb naming the draft or revision being edited — or “Current” — with
     * a menu switching between them.
     *
     * @return array<string, mixed>|null
     */
    private function revisionCrumb(): ?array
    {
        $contextMenu = $this->contextMenu();

        if ($contextMenu === null) {
            return null;
        }

        $label = t('Current');
        $items = [];
        $groupIndex = null;

        foreach ($contextMenu['items'] as $item) {
            if ($item['type'] === 'heading') {
                $items[] = ['type' => 'group', 'heading' => $item['label'], 'items' => []];
                $groupIndex = array_key_last($items);

                continue;
            }

            if ($item['type'] === 'hr') {
                $items[] = ['type' => 'hr'];
                $groupIndex = null;

                continue;
            }

            if ($item['selected']) {
                $label = $item['label'];
            }

            // The description (“Saved … by …”) rides along for the command
            // palette, which offers these as actions and shows it as a hint.
            $link = Arr::only($item, ['type', 'label', 'description', 'href', 'selected']);

            if ($groupIndex === null) {
                $items[] = $link;
            } else {
                $items[$groupIndex]['items'][] = $link;
            }
        }

        return [
            'id' => 'revision-crumb',
            'label' => $label,
            'items' => $items,
        ];
    }

    /**
     * A crumb naming the site being edited, with a menu linking to the same
     * element on every other editable site it propagates to.
     *
     * @return array<string, mixed>|null
     */
    private function siteCrumb(): ?array
    {
        if (! $this->element::isLocalized() || ! Sites::isMultiSite()) {
            return null;
        }

        $editableSiteIds = Sites::getEditableSiteIds()->all();
        $siteIds = array_values(array_intersect(
            array_column(
                array_filter(
                    ElementHelper::supportedSitesForElement($this->element, true),
                    fn (array $site): bool => $site['propagate'],
                ),
                'siteId',
            ),
            $editableSiteIds,
        ));

        if (count($siteIds) < 2) {
            return null;
        }

        $currentSite = $this->element->getSite();
        // Keep every other query param so switching sites doesn't drop the
        // draft, revision, or return URL the editor was opened with.
        $params = Arr::except($this->request->query(), ['fresh', 'site']);
        $path = $this->request->craftPath();

        $items = [];

        foreach ($siteIds as $siteId) {
            $site = Sites::getSiteById($siteId);

            if ($site === null) {
                continue;
            }

            $items[] = [
                'type' => 'link',
                'label' => t($site->getName(), category: 'site'),
                'href' => Url::cpUrl($path, ['site' => $site->handle] + $params),
                'selected' => $site->id === $currentSite->id,
            ];
        }

        return [
            // Marks this as the screen's own site switcher, so the shared one
            // every screen leads with stands down rather than adding a second.
            'id' => 'site-crumb',
            'icon' => Icons::earth(),
            'label' => t($currentSite->getName(), category: 'site'),
            'items' => $items,
        ];
    }

    public function readOnly(): bool
    {
        return ! $this->canSave;
    }

    private function attachedRelease(): ?Release
    {
        if (! $this->attachedReleaseResolved) {
            $this->attachedReleaseResolved = true;
            $this->attachedRelease = $this->element->getIsDraft() && ! $this->element->isProvisionalDraft
                ? app(Releases::class)->releaseForDraft($this->element)
                : null;
        }

        return $this->attachedRelease;
    }

    /**
     * The compiled field layout. `null` when the element has no field layout —
     * the page then renders its sidebar islands alone.
     */
    public function ui(): ?UiPayload
    {
        if ($this->uiResolved) {
            return $this->ui;
        }

        $this->uiResolved = true;
        $fieldLayout = $this->element->getFieldLayout();

        if ($fieldLayout === null) {
            return $this->ui = null;
        }

        // Delta tracking stays active so the compiled payload carries the same
        // initial values the autosave path compares against later in the stack.
        return $this->ui = DeltaRegistry::withActive(true, fn () => app(FieldLayoutCompiler::class)->compile(
            $fieldLayout,
            $this->element,
            new UiContext(
                namespace: [],
                errors: $this->element->uiErrors(),
                mode: $this->canSave ? ControlMode::Editable : ControlMode::ReadOnly,
                refreshable: true,
            ),
        ));
    }

    /**
     * Element types without statuses only get a label for drafts.
     */
    public function statusLabelHtml(): ?string
    {
        if (! $this->element::hasStatuses() && $this->element->getStatus() !== Element::STATUS_DRAFT) {
            return null;
        }

        return app(StatusHtml::class)->componentStatusLabelHtml($this->element);
    }

    /**
     * The element's meta fields (entry type, slug, parent, post date, status,
     * notes …) as a second UI, rendered into the editor sidebar.
     *
     * Keeping it separate from {@see ui()} lets the two render in different
     * regions while both submit through the same Inertia form.
     */
    public function sidebarUi(): ?UiPayload
    {
        if ($this->sidebarUiResolved) {
            return $this->sidebarUi;
        }

        $this->sidebarUiResolved = true;
        $context = $this->sidebarUiContext();
        $ui = $this->element->sidebarUi($context);

        return $this->sidebarUi = $ui === null
            ? null
            : app(UiResolver::class)->resolve($ui, $context);
    }

    private function sidebarUiContext(): UiContext
    {
        return new UiContext(
            namespace: [],
            errors: $this->element->uiErrors(),
            mode: $this->canSave ? ControlMode::Editable : ControlMode::ReadOnly,
        );
    }

    public function metadataHtml(): ?string
    {
        return $this->element->id
            ? app(ContentHtml::class)->metadataHtml(...$this->element->getMetadataGroups())
            : null;
    }
}
