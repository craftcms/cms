<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\ViewModels;

use CraftCms\Cms\Cms;
use CraftCms\Cms\Cp\Html\ContentHtml;
use CraftCms\Cms\Cp\Html\StatusHtml;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\ElementHelper;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\FieldLayout\FieldLayoutCompiler;
use CraftCms\Cms\Form\Enums\ControlMode;
use CraftCms\Cms\Form\FormContext;
use CraftCms\Cms\Form\FormPayload;
use CraftCms\Cms\Form\FormResolver;
use CraftCms\Cms\Http\Controllers\Elements\Concerns\EditsElement;
use CraftCms\Cms\Http\Controllers\Elements\Concerns\ElementCrumbs;
use CraftCms\Cms\Http\Requests\ElementRequest;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\DeltaRegistry;
use CraftCms\Cms\Support\Facades\I18N;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Support\Url;
use CraftCms\Cms\Translation\Locale;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Gate;

use function CraftCms\Cms\t;

/**
 * The shared Inertia payload for an element edit screen.
 *
 * The element's field layout is compiled to a {@see FormPayload} through the
 * same {@see FieldLayoutCompiler} the legacy editor and the slideouts use, so
 * both renderers stay in sync; everything page-shaped (titles, crumbs, the save
 * target, and the sidebar islands) lives here. Tabs belong to the Form renderer.
 *
 * Element-type view models extend this with their own payload keys (public
 * methods) and supply their save route via {@see saveUrl()} — e.g. entries
 * post to the entry store action, assets to their own.
 *
 * Public methods are payload keys (see {@see ViewModel}); shared intermediates
 * (the compiled form) are memoized privately since payload methods may be
 * invoked in any order.
 */
abstract class ElementEditViewModel extends ViewModel
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

    private ?FormPayload $form = null;

    private bool $formResolved = false;

    private ?FormPayload $sidebarForm = null;

    private bool $sidebarFormResolved = false;

    public function __construct(
        protected readonly ElementInterface $element,
        ElementRequest $request,
        protected readonly bool $canSave = true,
        protected readonly bool $mergedCanonicalChanges = false,
    ) {
        $this->request = $request;
    }

    /**
     * The element type's own store action. The Form payload submits ordinary
     * nested input names, so the existing save controllers read it without a
     * translation layer.
     */
    abstract protected function elementSaveUrl(): string;

    /**
     * Adopts an already-compiled field layout instead of compiling one.
     *
     * Autosave compiles the layout for its own response before it rebuilds this
     * payload; handing that compilation over is what keeps a keystroke from
     * paying for the same work twice. Takes an argument, so {@see ViewModel}
     * leaves it out of the payload.
     */
    public function withForm(?FormPayload $form): static
    {
        $this->form = $form;
        $this->formResolved = true;

        return $this;
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
     * The alternate save actions offered beside the Save button — "Save and
     * continue editing", "Save as a new …", and friends.
     *
     * Each entry submits the edit form as it stands, optionally to a different
     * action and with extra params. `actionUrl` is null when the item posts to
     * the screen's ordinary save target, which the client resolves at submit
     * time (see {@see applyDraftUrl()}).
     *
     * @return list<array{label: string, actionUrl: string|null, params: array<string, mixed>, redirect: string|null, shortcut: bool, shift: bool}>
     */
    public function formActions(): array
    {
        if (! $this->canSave) {
            return [];
        }

        return array_map(fn (array $action): array => [
            'label' => (string) $action['label'],
            'actionUrl' => isset($action['action']) ? Url::actionUrl($action['action']) : null,
            'params' => isset($action['action'])
                ? [...$this->genericIdentityParams(), ...($action['params'] ?? [])]
                : ($action['params'] ?? []),
            // Redirects cross the wire encrypted; the save controllers decrypt
            // them and render `{cpEditUrl}` against the saved element.
            'redirect' => isset($action['redirect']) ? Crypt::encrypt($action['redirect']) : null,
            'shortcut' => (bool) ($action['shortcut'] ?? false),
            'shift' => (bool) ($action['shift'] ?? false),
        ], $this->element->getAltActions());
    }

    /**
     * Buttons shown beside Save.
     *
     * Applying a named draft and reverting a revision belong here too, but
     * those screens still render through the legacy editor, so they arrive with
     * the draft/revision work rather than as unreachable buttons.
     *
     * @return list<array{label: string, actionUrl: string, params: array<string, mixed>, redirect: string|null, variant: string}>
     */
    public function headerActions(): array
    {
        $element = $this->element;
        $canonical = $element->getCanonical(true);
        $isCurrent = $element->getIsCanonical() || $element->isProvisionalDraft;
        $actions = [];

        if ($isCurrent && ! $element->getIsUnpublishedDraft() && Gate::check('createDrafts', $canonical)) {
            $actions[] = [
                'label' => t('Create a draft'),
                'actionUrl' => Url::actionUrl('elements/save-draft'),
                'params' => [
                    ...$this->genericIdentityParams(),
                    // Without this the provisional draft is promoted in place
                    // rather than a separate named draft being created.
                    'dropProvisional' => 1,
                ],
                'redirect' => Crypt::encrypt('{cpEditUrl}'),
                'variant' => 'outline',
            ];
        }

        // Applying a named draft writes it onto the canonical element. The
        // provisional case isn't here: for those the Save button *is* apply.
        if (
            $element->getIsDraft() &&
            ! $isCurrent &&
            $this->canSave &&
            Gate::check('saveCanonical', $element)
        ) {
            $actions[] = [
                'label' => t('Apply draft'),
                'actionUrl' => Url::actionUrl('elements/apply-draft'),
                'params' => [
                    ...$this->genericIdentityParams(),
                    'draftId' => $element->draftId,
                ],
                'redirect' => Crypt::encrypt('{cpEditUrl}'),
                'variant' => 'secondary',
            ];
        }

        if (
            $element->getIsRevision() &&
            $element->hasRevisions() &&
            Gate::check('saveCanonical', $element)
        ) {
            $actions[] = [
                'label' => t('Revert content from this revision'),
                'actionUrl' => Url::actionUrl('elements/revert'),
                'params' => [
                    ...$this->genericIdentityParams(),
                    'revisionId' => $element->revisionId,
                ],
                'redirect' => Crypt::encrypt('{cpEditUrl}'),
                'variant' => 'outline',
            ];
        }

        // Read-only users who may still branch the element get the duplicate
        // path instead, since they can't save over the canonical one.
        if (
            ! $this->canSave &&
            ! $element->getIsRevision() &&
            Gate::check('duplicateAsDraft', $element)
        ) {
            $actions[] = [
                'label' => t('Save as a new {type}', ['type' => $element::lowerDisplayName()]),
                'actionUrl' => Url::actionUrl('elements/duplicate'),
                'params' => [
                    ...$this->genericIdentityParams(),
                    'asUnpublishedDraft' => 1,
                ],
                'redirect' => Crypt::encrypt('{cpEditUrl}'),
                'variant' => 'outline',
            ];
        }

        return $actions;
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
            ? Url::actionUrl('elements/recent-activity', ['dontExtendSession' => 1])
            : null;
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
        $element = $this->element;

        if (! $element->id) {
            return [];
        }

        $targets = $element->getPreviewTargets();

        if ($targets === []) {
            return [];
        }

        $isLive = (
            ($element->getIsCanonical() || $element->isProvisionalDraft) &&
            ! $element->getIsDraft() &&
            $element->enabled &&
            $element->getEnabledForSite() &&
            $element->getRoute() !== null
        );

        $previewToken = Str::random(32, extendedChars: true);
        $siteToken = (app()->isDownForMaintenance() || ! $element->getSite()->getEnabled())
            ? Crypt::encrypt((string) $element->siteId)
            : null;

        $tokenParams = array_filter([
            'elementType' => $element::class,
            'canonicalId' => $element->getCanonicalId(),
            'siteId' => $element->siteId,
            'revisionId' => $element->revisionId,
            'draftId' => $element->isProvisionalDraft ? null : $element->draftId,
            'previewToken' => Crypt::encrypt($previewToken),
        ], fn (mixed $value): bool => $value !== null);

        return array_values(array_map(function (array $target) use ($isLive, $previewToken, $siteToken, $tokenParams): array {
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

            $params[Cms::config()->tokenParam] = $previewToken;

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
     * The element's action menu, as behavior descriptors the client dispatches.
     *
     * "View in a new tab" is dropped once preview targets exist, since the
     * preview control already covers it, and "Edit" never appears — this screen
     * is the edit screen.
     *
     * @return list<array<string, mixed>>
     */
    public function actionMenu(): array
    {
        if (! $this->element->id) {
            return [];
        }

        $hidesView = $this->element->getPreviewTargets() !== [];

        return array_values(array_filter(
            $this->element->actionMenuDescriptors(),
            fn (array $item): bool => ! (
                $hidesView && ($item['behavior']['type'] ?? null) === 'link'
            ),
        ));
    }

    /**
     * The drafts-and-revisions switcher shown beside the breadcrumbs.
     *
     * Groups are flattened into a single item list — headings become `heading`
     * entries the client renders as non-interactive rows — because the action
     * menu's item contract has no nested-group shape.
     *
     * @return array<string, mixed>|null
     */
    public function contextMenu(): ?array
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

    /**
     * Identity params the shared `elements/*` actions need to resolve the
     * element. The type-specific save controllers key off their own params
     * (an entry's `entryId`), so these only apply to the generic endpoints.
     *
     * @return array{elementType: class-string<ElementInterface>, elementId: int|null, siteId: int|null}
     */
    private function genericIdentityParams(): array
    {
        return [
            'elementType' => $this->element::class,
            'elementId' => $this->element->getCanonical(true)->id,
            'siteId' => $this->element->siteId,
        ];
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

    /**
     * The Save button's label. Saving a named draft saves the draft rather than
     * the element, and an unpublished draft creates the element outright.
     */
    public function submitButtonLabel(): string
    {
        $element = $this->element;

        if ($element->getIsUnpublishedDraft()) {
            return Gate::check('saveCanonical', $element)
                ? mb_ucfirst(t('Create {type}', ['type' => $element::lowerDisplayName()]))
                : mb_ucfirst(t('Save {type}', ['type' => t('draft')]));
        }

        if ($element->getIsDraft() && ! $element->isProvisionalDraft) {
            return mb_ucfirst(t('Save {type}', ['type' => t('draft')]));
        }

        return t('Save');
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
     * @return list<array<string, mixed>>
     */
    public function crumbs(): array
    {
        $crumbs = $this->elementCrumbs($this->element);

        $siteCrumb = $this->siteCrumb();

        return $siteCrumb === null ? $crumbs : [$siteCrumb, ...$crumbs];
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
            'icon' => 'earth',
            'label' => t($currentSite->getName(), category: 'site'),
            'actions' => $items,
        ];
    }

    public function readOnly(): bool
    {
        return ! $this->canSave;
    }

    /**
     * The compiled field layout. `null` when the element has no field layout —
     * the page then renders its sidebar islands alone.
     */
    public function form(): ?FormPayload
    {
        if ($this->formResolved) {
            return $this->form;
        }

        $this->formResolved = true;
        $fieldLayout = $this->element->getFieldLayout();

        if ($fieldLayout === null) {
            return $this->form = null;
        }

        // Delta tracking stays active so the compiled payload carries the same
        // initial values the autosave path compares against later in the stack.
        return $this->form = DeltaRegistry::withActive(true, fn () => app(FieldLayoutCompiler::class)->compile(
            $fieldLayout,
            $this->element,
            new FormContext(
                namespace: [],
                errors: $this->element->formErrors(),
                mode: $this->canSave ? ControlMode::Editable : ControlMode::ReadOnly,
                refreshable: true,
            ),
        ));
    }

    public function statusLabelHtml(): string
    {
        return app(StatusHtml::class)->componentStatusLabelHtml($this->element);
    }

    /**
     * The element's meta fields (entry type, slug, parent, post date, status,
     * notes …) as a second Form, rendered into the editor sidebar.
     *
     * Keeping it separate from {@see form()} lets the two render in different
     * regions while both submit through the same Inertia form.
     */
    public function sidebarForm(): ?FormPayload
    {
        if ($this->sidebarFormResolved) {
            return $this->sidebarForm;
        }

        $this->sidebarFormResolved = true;
        $context = $this->sidebarFormContext();
        $form = $this->element->sidebarForm($context);

        return $this->sidebarForm = $form === null
            ? null
            : app(FormResolver::class)->resolve($form, $context);
    }

    private function sidebarFormContext(): FormContext
    {
        return new FormContext(
            namespace: [],
            errors: $this->element->formErrors(),
            mode: $this->canSave ? ControlMode::Editable : ControlMode::ReadOnly,
        );
    }

    public function metadataHtml(): ?string
    {
        return $this->element->id
            ? app(ContentHtml::class)->metadataHtml($this->element->getMetadata())
            : null;
    }
}
