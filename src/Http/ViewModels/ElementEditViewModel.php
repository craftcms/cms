<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\ViewModels;

use CraftCms\Cms\Cp\Html\ContentHtml;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\ElementHelper;
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
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Url;

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
    ) {
        $this->request = $request;
    }

    /**
     * Where the edit form posts. Element types point this at their own store
     * action — the Form payload submits ordinary nested input names, so the
     * existing save controllers read it without a translation layer.
     */
    abstract public function saveUrl(): string;

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
        $crumbs = array_map(function (array $crumb): array {
            if (isset($crumb['url'])) {
                $crumb['url'] = Url::cpUrl($crumb['url']);
            }

            return $crumb;
        }, $this->elementCrumbs($this->element));

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
                errors: $this->element->errors()->getMessages(),
                mode: $this->canSave ? ControlMode::Editable : ControlMode::ReadOnly,
                refreshable: true,
            ),
        ));
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
            errors: $this->element->errors()->getMessages(),
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
