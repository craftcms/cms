<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Responses;

use CraftCms\Cms\Cms;
use CraftCms\Cms\Cp\Data\NavItem;
use CraftCms\Cms\Cp\Html\MenuHtml;
use CraftCms\Cms\Support\Facades\DeltaRegistry;
use CraftCms\Cms\Support\Facades\HtmlStack;
use CraftCms\Cms\Support\Facades\InputNamespace;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Support\Url;
use CraftCms\Cms\View\LegacyAssets\ContentWindowAsset;
use CraftCms\Cms\View\LegacyAssets\InternalAssetRegistry;
use CraftCms\Cms\View\TemplateMode;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Traits\Conditionable;
use Inertia\Inertia;
use Stringable;
use Symfony\Component\HttpFoundation\Response;

use function CraftCms\Cms\pageTemplate;
use function CraftCms\Cms\t;
use function CraftCms\Cms\template;

class CpScreenResponse implements Responsable
{
    use Conditionable;

    /**
     * @var string|null The Inertia page component to render.
     *
     * @see inertiaPage()
     */
    private ?string $inertiaPage = null;

    /**
     * @var array<string, mixed>|Arrayable<string, mixed> Props to pass to the Inertia page component.
     *
     * @see inertiaPage()
     */
    private array|Arrayable $inertiaProps = [];

    /**
     * @var callable|null Callable that will be called before other properties are added to the screen.
     *
     * @see prepareScreen()
     */
    private mixed $prepareScreen = null;

    /**
     * @var string|null The control panel edit URL for this screen.
     *
     * @see editUrl()
     */
    public ?string $editUrl = null;

    /**
     * @var string|null The document title. If null, [[title]] will be used.
     *
     * This will only be used by full-page screens.
     *
     * @see docTitle()
     */
    public ?string $docTitle = null;

    /**
     * @var string|null The page title.
     *
     * This will only be used by full-page screens.
     *
     * @see title()
     */
    public ?string $title = null;

    /**
     * @var string|null The selected subnav item’s key in the global sidebar.
     *
     * This will only be used by full-page screens.
     *
     * @see selectedSubnavItem()
     */
    public ?string $selectedSubnavItem = null;

    /**
     * @var list<array<string, mixed>>|callable|null Breadcrumbs.
     *
     * This will only be used by full-page screens.
     *
     * @see crumbs()
     * @see addCrumb()
     */
    public $crumbs;

    /**
     * @var array<string, array<string, mixed>> Tabs.
     *
     * @see tabs()
     * @see addTab()
     */
    public array $tabs = [];

    /**
     * @var list<array<string, mixed>|NavItem>|null Secondary navigation items.
     *
     * @see subnav()
     */
    public ?array $subnav = null;

    /**
     * @var string|null Class that should be added to the slideout body.
     */
    public ?string $slideoutBodyClass = null;

    /**
     * @var array<string, mixed> Extra data merged into the Inertia `screen`
     *                           prop, for screens whose client-side behavior
     *                           needs configuring.
     *
     * @see screenData()
     */
    private array $screenData = [];

    /**
     * @var array<string, mixed> Custom attributes to add to the `<main>` tag.
     *
     * See [[\CraftCms\Cms\Support\Html::renderTagAttributes()]] for supported attribute syntaxes.
     *
     * This will only be used by full-page screens.
     *
     * @see mainAttributes()
     */
    public array $mainAttributes = [];

    /**
     * @var array<string, mixed> Custom attributes to add to the `<form>` tag.
     *
     * See [[\CraftCms\Cms\Support\Html::renderTagAttributes()]] for supported attribute syntaxes.
     *
     * @see formAttributes()
     */
    public array $formAttributes = [];

    /**
     * @var string|null The form action.
     *
     * @see action()
     */
    public ?string $action = null;

    /**
     * @var list<array<string, mixed>>|callable|null Alternate form actions.
     *
     * This will only be used by full-page screens.
     *
     * @see altActions()
     * @see addAltAction()
     */
    public $altActions;

    /**
     * @var string|null The URL the form should redirect to after posting.
     *
     * This will only be used by full-page screens.
     *
     * @see redirectUrl()
     */
    public ?string $redirectUrl = null;

    /**
     * @var string|null The URL the form should redirect to after posting, if submitted via the
     *                  <kbd>Ctrl</kbd><kbd>Command</kbd> + <kbd>S</kbd> keyboard shortcut.
     *
     * This will only be used by full-page screens.
     *
     * @see saveShortcutRedirectUrl()
     */
    public ?string $saveShortcutRedirectUrl = null;

    /**
     * @var callable|null Context menu items factory.
     *
     * @see contextMenuItems()
     */
    public $contextMenuItems;

    /**
     * @var string|Stringable|callable|null Toolbar HTML
     *
     * @see toolbarHtml()
     * @see toolbarTemplate()
     */
    public $toolbarHtml;

    /**
     * @var callable|null Action menu items factory.
     *
     * @see actionMenuItems()
     */
    public $actionMenuItems;

    /**
     * @var string|null The submit button label.
     *
     * @see submitButtonLabel()
     */
    public ?string $submitButtonLabel = null;

    /**
     * @var string|Stringable|callable|null Additional buttons’ HTML.
     *
     * This will only be used by full-page screens.
     *
     * @see additionalButtonsHtml()
     * @see additionalButtonsTemplate()
     */
    public $additionalButtonsHtml;

    /**
     * @var string|Stringable|callable|null The content HTML.
     *
     * @see contentHtml()
     * @see contentTemplate()
     */
    public $contentHtml;

    /**
     * @var string|Stringable|callable|null The right-hand meta sidebar HTML.
     *
     * @see metaSidebarHtml()
     * @see metaSidebarTemplate()
     */
    public $metaSidebarHtml;

    /**
     * @var string|Stringable|callable|null The left-hand page sidebar HTML (only used by full-page screens).
     *
     * @see pageSidebarHtml()
     * @see pageSidebarTemplate()
     */
    public $pageSidebarHtml;

    /**
     * @var string|Stringable|callable|null The content notice HTML.
     *
     * @see noticeHtml()
     * @see noticeTemplate()
     */
    public $noticeHtml;

    /**
     * @var string|Stringable|callable|null The errors summary HTML (DEV-212).
     *
     * @see errorSummary()
     * @see errorSummaryTemplate()
     */
    public $errorSummary;

    /**
     * Sets a callable that will be called before other properties are added to the screen.
     */
    public function prepareScreen(?callable $value): self
    {
        $this->prepareScreen = $value;

        return $this;
    }

    /**
     * Sets the control panel edit URL for this screen.
     */
    public function editUrl(?string $value): self
    {
        $this->editUrl = $value;

        return $this;
    }

    /**
     * Sets the document title.
     *
     * This will only be used by full-page screens.
     */
    public function docTitle(?string $value): self
    {
        $this->docTitle = $value;

        return $this;
    }

    /**
     * Sets the page title.
     *
     * This will only be used by full-page screens.
     */
    public function title(?string $value): self
    {
        $this->title = $value;

        return $this;
    }

    /**
     * Sets the selected subnav item’s key in the global sidebar.
     *
     * This will only be used by full-page screens.
     */
    public function selectedSubnavItem(?string $value): self
    {
        $this->selectedSubnavItem = $value;

        return $this;
    }

    /**
     * Sets the breadcrumbs.
     *
     * A breadcrumb is shaped like a link action item, so the same array can be
     * used as a crumb and as an entry in another crumb's menu:
     *
     * - `label` – The breadcrumb label, to be HTML-encoded
     * - `href` – The URL the breadcrumb links to. Absolute: nothing normalizes
     *   it downstream, so build it with [[\CraftCms\Cms\Support\Url::cpUrl()]]
     * - `icon` – The icon displayed beside the label
     *
     * Plus three keys only a crumb uses:
     *
     * - `html` – Server-rendered crumb content (e.g. an element chip), used
     *   instead of `label`
     * - `attrs` – Extra HTML attributes for the crumb
     * - `actions` – A dropdown of link action items shown alongside the crumb
     *   (e.g. the other sections available from an entry's section crumb)
     *
     * The last crumb is treated as the current page — `aria-current` is derived
     * from position, so there is no `current` key.
     *
     * This will only be used by full-page screens.
     */
    /** @param list<array<string, mixed>>|callable|null $value */
    public function crumbs(callable|array|null $value): self
    {
        $this->crumbs = $value;

        return $this;
    }

    /**
     * Adds a breadcrumb.
     *
     * This will only be used by full-page screens.
     */
    public function addCrumb(string $label, ?string $url = null): self
    {
        if (! is_array($this->crumbs)) {
            $this->crumbs = [];
        }

        $this->crumbs[] = [
            'label' => $label,
            'href' => $url ? Url::cpUrl($url) : null,
        ];

        return $this;
    }

    /**
     * Sets the tabs.
     *
     * Each tab should be represented by a nested array with the following keys:
     *
     * - `label` – The human-facing tab label.
     * - `url` – The `href` attribute of the tab’s anchor. Set to `#container-ids` if the tabs are meant to toggle in-page content.
     * - `class` _(optional)_ - Class name(s) that should be added to the tab’s anchor.
     * - `visible` _(optional)_ – Whether the tab should be initially visible (defaults to `true`).
     *
     * If the tabs are meant to toggle in-page content, the array keys should be set to the `id` attributes of the
     * container elements they represent.
     */
    /** @param array<string, array<string, mixed>> $value */
    public function tabs(array $value): self
    {
        $this->tabs = $value;

        return $this;
    }

    /**
     * Sets the secondary navigation items.
     *
     * A list, not a keyed array: the CP shell counts these to decide whether to
     * draw the secondary nav, which a JSON object wouldn't let it do.
     */
    /** @param list<array<string, mixed>|NavItem>|null $value */
    public function subnav(?array $value): self
    {
        $this->subnav = $value;

        return $this;
    }

    /**
     * Adds a tab.
     *
     * @param  string|string[]|null  $class
     */
    public function addTab(
        string $id,
        string $label,
        string $url,
        array|string|null $class = null,
        bool $visible = true,
    ): self {
        $this->tabs[$id] = [
            'label' => $label,
            'url' => $url,
            'class' => Html::explodeClass($class),
            'visible' => $visible,
        ];

        return $this;
    }

    /**
     * Sets custom attributes that should be added to the `<main>` tag.
     *
     * See [[\CraftCms\Cms\Support\Html::renderTagAttributes()]] for supported attribute syntaxes.
     *
     * This will only be used by full-page screens.
     */
    /** @param array<string, mixed> $value */
    public function mainAttributes(array $value): self
    {
        $this->mainAttributes = $value;

        return $this;
    }

    /**
     * Sets custom attributes that should be added to the `<form>` tag.
     *
     * See [[\CraftCms\Cms\Support\Html::renderTagAttributes()]] for supported attribute syntaxes.
     */
    /** @param array<string, mixed> $value */
    public function formAttributes(array $value): self
    {
        $this->formAttributes = $value;

        return $this;
    }

    /**
     * Sets the form action.
     */
    public function action(?string $value): self
    {
        $this->action = $value;

        return $this;
    }

    /**
     * Sets alternate form actions.
     *
     * Each action should be represented by a nested array with the following keys:
     *
     * - `label` – The human-facing action label.
     * - `destructive` _(optional)_ – Whether the action should be considered destructive (defaults to `false`).
     * - `action` _(optional)_ – The controller action that should be posted to.
     * - `redirect` _(optional)_ – The URL the form should redirect to afterwards.
     * - `confirm` _(optional)_ – A confirmation message that should be shown.
     * - `params` _(optional)_ – Array of additional params that should be posted.
     * - `eventData` _(optional)_ – Additional properties that should be assigned to the JavaScript `submit` event.
     * - `shortcut` _(optional)_ – Whether the action can be triggered with a <kbd>Command</kbd>/<kbd>Ctrl</kbd> + <kbd>S</kbd> keyboard shortcut
     *   (or <kbd>Command</kbd>/<kbd>Ctrl</kbd> + <kbd>Shift</kbd> + <kbd>S</kbd> if `'shift' => true` is also set).
     * - `retainScroll` _(optional)_ – Whether the browser should retain its scroll position on the next page.
     *
     * This will only be used by full-page screens.
     */
    /** @param list<array<string, mixed>>|callable|null $value */
    public function altActions(callable|array|null $value): self
    {
        $this->altActions = $value;

        return $this;
    }

    /**
     * Adds an alternate form action.
     *
     * This will only be used by full-page screens.
     *
     * @see altActions()
     */
    /** @param array<string, mixed> $config */
    public function addAltAction(string $label, array $config): self
    {
        if (! is_array($this->altActions)) {
            $this->altActions = [];
        }
        $this->altActions[] = ['label' => $label] + $config;

        return $this;
    }

    /**
     * Sets the URL the form should redirect to after posting.
     *
     * This will only be used by full-page screens.
     */
    public function redirectUrl(?string $value): self
    {
        $this->redirectUrl = $value;

        return $this;
    }

    /**
     * Sets URL the form should redirect to after posting, if submitted via the
     * <kbd>Ctrl</kbd><kbd>Command</kbd> + <kbd>S</kbd> keyboard shortcut.
     *
     * This will only be used by full-page screens.
     */
    public function saveShortcutRedirectUrl(?string $value): self
    {
        $this->saveShortcutRedirectUrl = $value;

        return $this;
    }

    /**
     * Sets the context menu items.
     *
     * See [[\CraftCms\Cms\Cp\Html\MenuHtml::disclosureMenu()]] for documentation on supported item properties.
     */
    public function contextMenuItems(?callable $value): self
    {
        $this->contextMenuItems = $value;

        return $this;
    }

    /**
     * Sets the toolbar HTML.
     */
    public function toolbarHtml(callable|string|Stringable|null $value): self
    {
        $this->toolbarHtml = $value;

        return $this;
    }

    /**
     * Sets a template that should be used to render the toolbar HTML.
     */
    /** @param array<string, mixed> $variables */
    public function toolbarTemplate(string $template, array $variables = []): self
    {
        return $this->toolbarHtml(
            fn () => template($template, $variables, templateMode: TemplateMode::Cp),
        );
    }

    /**
     * Sets the action menu items.
     *
     * See [[\CraftCms\Cms\Cp\Html\MenuHtml::disclosureMenu()]] for documentation on supported item properties.
     */
    public function actionMenuItems(?callable $value): self
    {
        $this->actionMenuItems = $value;

        return $this;
    }

    /**
     * Sets the submit button label.
     */
    public function submitButtonLabel(?string $value): self
    {
        $this->submitButtonLabel = $value;

        return $this;
    }

    /**
     * Sets the additional buttons’ HTML.
     *
     * This will only be used by full-page screens.
     */
    public function additionalButtonsHtml(callable|string|Stringable|null $value): self
    {
        $this->additionalButtonsHtml = $value;

        return $this;
    }

    /**
     * Sets a template that should be used to render the additional buttons’ HTML.
     *
     * This will only be used by full-page screens.
     */
    /** @param array<string, mixed> $variables */
    public function additionalButtonsTemplate(string $template, array $variables = []): self
    {
        return $this->additionalButtonsHtml(
            fn () => template($template, $variables, templateMode: TemplateMode::Cp),
        );
    }

    /**
     * Sets the content HTML.
     */
    public function contentHtml(callable|string|Stringable|null $value): self
    {
        $this->contentHtml = $value;

        return $this;
    }

    /**
     * Sets a template that should be used to render the content HTML.
     */
    /** @param array<string, mixed> $variables */
    public function contentTemplate(string $template, array $variables = []): self
    {
        return $this->contentHtml(
            fn () => template($template, $variables, templateMode: TemplateMode::Cp),
        );
    }

    /**
     * Sets the Inertia page component and props for this screen.
     *
     * When set, `toResponse()` will render an Inertia response instead of a Twig template.
     * The `title` and `crumbs` properties will be automatically included as props.
     */
    /** @param array<string, mixed>|Arrayable<string, mixed> $props */
    public function inertiaPage(?string $value, array|Arrayable $props = []): self
    {
        $this->inertiaPage = $value;
        $this->inertiaProps = $props;

        return $this;
    }

    /**
     * Sets the right-hand meta sidebar HTML.
     */
    public function metaSidebarHtml(callable|string|Stringable|null $value): self
    {
        $this->metaSidebarHtml = $value;

        return $this;
    }

    /**
     * Sets a template that should be used to render the right-hand meta sidebar HTML.
     */
    /** @param array<string, mixed> $variables */
    public function metaSidebarTemplate(string $template, array $variables = []): self
    {
        return $this->metaSidebarHtml(
            fn () => template($template, $variables, templateMode: TemplateMode::Cp),
        );
    }

    /**
     * Sets the left-hand page sidebar HTML (only used by full-page screens).
     */
    public function pageSidebarHtml(callable|string|Stringable|null $value): self
    {
        $this->pageSidebarHtml = $value;

        return $this;
    }

    /**
     * Sets a template that should be used to render the left-hand page sidebar HTML (only used by full-page screens).
     */
    /** @param array<string, mixed> $variables */
    public function pageSidebarTemplate(string $template, array $variables = []): self
    {
        return $this->pageSidebarHtml(
            fn () => template($template, $variables, templateMode: TemplateMode::Cp),
        );
    }

    /**
     * Sets the content notice HTML.
     */
    public function noticeHtml(callable|string|Stringable|null $value): self
    {
        $this->noticeHtml = $value;

        return $this;
    }

    /**
     * Sets a template that should be used to render the content notice HTML.
     */
    /** @param array<string, mixed> $variables */
    public function noticeTemplate(string $template, array $variables = []): self
    {
        return $this->noticeHtml(
            fn () => template($template, $variables, templateMode: TemplateMode::Cp),
        );
    }

    /**
     * Sets the errors summary HTML.
     */
    public function errorSummary(callable|string|Stringable|null $value): self
    {
        $this->errorSummary = $value;

        return $this;
    }

    /**
     * Sets a template that should be used to render the errors summary HTML.
     */
    /** @param array<string, mixed> $variables */
    public function errorSummaryTemplate(string $template, array $variables = []): self
    {
        return $this->errorSummary(
            fn () => template($template, $variables, templateMode: TemplateMode::Cp),
        );
    }

    /**
     * Merge extra data into the Inertia `screen` prop.
     *
     * For screens that hand configuration to client-side code. The Twig/jQuery
     * paths pass such config by injecting a script that looks the container up
     * by id — which races Vue's mount, since the panel's subtree isn't in the
     * document yet when that script runs. Props arrive with the page instead.
     *
     * @param  array<string, mixed>  $data
     */
    public function screenData(array $data): self
    {
        $this->screenData = [...$this->screenData, ...$data];

        return $this;
    }

    public function toResponse($request): Response
    {
        if ($request->wantsJson()) {
            return $this->slideoutResponse($request);
        }

        return $this->response($request);
    }

    /**
     * Render the screen into a slideout.
     *
     * Two clients ask for this, and they want different wire formats. The Vue
     * client sends `X-Inertia` and gets an Inertia page it can mount as a
     * component; the legacy jQuery `CpScreenSlideout` gets the flat payload of
     * server-rendered HTML it has always got. Both are built from one pass over
     * the screen, so a screen behaves identically whichever one asks.
     *
     * A regular Inertia page visit never lands here: Inertia's own client sends
     * `Accept: text/html`, so `wantsJson()` is false for it.
     */
    private function slideoutResponse(Request $request): Response
    {
        $parts = $this->prepareSlideout($request);

        return $request->inertia()
            ? $this->slideoutInertiaResponse($request, $parts)
            : $this->slideoutJsonResponse($parts);
    }

    /**
     * Resolve the screen's parts under a per-request input namespace.
     *
     * The namespace keeps two slideouts of the same screen from colliding on
     * input names, so it has to wrap `prepareScreen` as well as the rendering.
     *
     * @return array<string, mixed>
     */
    private function prepareSlideout(Request $request): array
    {
        $namespace = Str::random(10);
        $containerId = $request->header('X-Craft-Container-Id');

        if ($this->prepareScreen) {
            abort_unless((bool) $containerId, 400, 'Request missing the X-Craft-Container-Id header.');

            InputNamespace::set($namespace);
            call_user_func($this->prepareScreen, $this, $containerId);
            InputNamespace::set(null);
        }

        $extraToolbarItems = is_callable($this->toolbarHtml) ? call_user_func($this->toolbarHtml) : $this->toolbarHtml;
        $notice = $this->noticeHtml ? InputNamespace::namespaceInputs($this->noticeHtml, $namespace) : null;

        $tabs = count($this->tabs) > 1 ? InputNamespace::namespaceInputs(fn () => template('_includes/tabs', [
            'tabs' => $this->tabs,
        ], templateMode: TemplateMode::Cp), $namespace) : null;

        $content = InputNamespace::namespaceInputs(function () {
            $components = [];

            if ($this->contentHtml) {
                $components[] = is_callable($this->contentHtml) ? call_user_func($this->contentHtml) : $this->contentHtml;
            }

            if ($this->action) {
                $components[] = Html::actionInput($this->action, [
                    'class' => 'action-input',
                ]);
            }

            return implode("\n", $components);
        }, $namespace);

        $sidebar = $this->metaSidebarHtml ? InputNamespace::namespaceInputs($this->metaSidebarHtml, $namespace) : null;
        $errorSummary = $this->errorSummary ? InputNamespace::namespaceInputs($this->errorSummary, $namespace) : null;

        // Read after everything above: rendering the screen is what pushes onto
        // the HTML stack and registers delta names.
        return [
            'namespace' => $namespace,
            'containerId' => $containerId,
            'extraToolbarItems' => $extraToolbarItems,
            'notice' => $notice,
            'tabs' => $tabs,
            'content' => $content,
            'inertiaPage' => $this->inertiaPage,
            'inertiaProps' => $this->inertiaProps instanceof Arrayable
                ? $this->inertiaProps->toArray()
                : $this->inertiaProps,
            'sidebar' => $sidebar,
            'errorSummary' => $errorSummary,
            'actionMenu' => $this->actionMenu(withDestructive: false, config: [
                'withButton' => false,
            ], namespace: $namespace),
        ];
    }

    /**
     * The legacy `Craft.CpScreenSlideout` payload.
     *
     * @param  array<string, mixed>  $parts
     */
    private function slideoutJsonResponse(array $parts): JsonResponse
    {
        return new JsonResponse([
            'editUrl' => $this->editUrl ? Url::cpUrl($this->editUrl) : null,
            'namespace' => $parts['namespace'],
            'title' => $this->title,
            'notice' => $parts['notice'],
            'tabs' => $parts['tabs'],
            'bodyClass' => $this->slideoutBodyClass,
            'formAttributes' => $this->formAttributes,
            'action' => $this->action,
            'extraToolbarItems' => $parts['extraToolbarItems'],
            'submitButtonLabel' => $this->submitButtonLabel,
            'actionMenu' => $parts['actionMenu'],
            'content' => $parts['content'],
            // The legacy slideout mounts a Vue page of its own when the screen
            // has one, so it needs these too — it isn't only the Inertia
            // payload's business.
            'inertiaPage' => $parts['inertiaPage'],
            'inertiaProps' => $parts['inertiaProps'],
            'sidebar' => $parts['sidebar'],
            'errorSummary' => $parts['errorSummary'],
            'headHtml' => HtmlStack::headHtml(),
            'bodyHtml' => HtmlStack::bodyHtml(),
            'deltaNames' => DeltaRegistry::getNames(),
            'initialDeltaValues' => DeltaRegistry::getInitialValues(),
        ]);
    }

    /**
     * The Inertia slideout payload.
     *
     * Screens that haven't been ported to a Vue page still render here — they
     * fall back to the `cp/Screen` component, which draws the same HTML
     * fragments the legacy payload carries.
     *
     * @param  array<string, mixed>  $parts
     */
    private function slideoutInertiaResponse(Request $request, array $parts): Response
    {
        return Inertia::render($this->inertiaPage ?? 'cp/Screen', $this->inertiaProps)
            ->with($this->screenProps('slideout', [
                'containerId' => $parts['containerId'],
                'namespace' => $parts['namespace'],
                'editUrl' => $this->editUrl ? Url::cpUrl($this->editUrl) : null,
                'bodyClass' => $this->slideoutBodyClass,
                'action' => $this->action,
                'formAttributes' => $this->formAttributes,
                'deltaNames' => DeltaRegistry::getNames(),
                'initialDeltaValues' => DeltaRegistry::getInitialValues(),
            ]))
            ->with([
                'title' => $this->title,
                'submitButtonLabel' => $this->submitButtonLabel,
                'actionMenu' => $parts['actionMenu'],
                'toolbar' => $parts['extraToolbarItems'],
                // Populated for every screen; `cp/Screen` renders them, and a
                // Vue page ignores them.
                'tabs' => $parts['tabs'],
                'contentNotice' => $parts['notice'],
                'content' => $parts['content'],
                'details' => $parts['sidebar'],
                'errorSummary' => $parts['errorSummary'],
            ])
            ->toResponse($request);
    }

    /**
     * Tells the client which context the screen is rendering in.
     *
     * `headHtml`/`bodyHtml` ride along as props because the blade root view —
     * where `HandleInertiaRequests` normally injects them — isn't rendered for
     * an XHR Inertia response.
     *
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    private function screenProps(string $mode, array $extra = [], bool $withAssets = true): array
    {
        return [
            'screen' => ['mode' => $mode] + $extra + $this->screenData,
            ...($withAssets ? [
                'headHtml' => HtmlStack::headHtml(),
                'bodyHtml' => HtmlStack::bodyHtml(),
            ] : []),
        ];
    }

    private function response(Request $request): Response
    {
        $isForm = (bool) $this->action;

        if ($this->prepareScreen) {
            call_user_func($this->prepareScreen, $this, $isForm ? 'main-form' : 'main');
        }

        $docTitle = $this->docTitle ?? strip_tags($this->title ?? '');
        $crumbs = (is_callable($this->crumbs) ? call_user_func($this->crumbs) : $this->crumbs) ?? [];
        $toolbar = is_callable($this->toolbarHtml) ? call_user_func($this->toolbarHtml) : $this->toolbarHtml;
        $addlButtons = is_callable($this->additionalButtonsHtml) ? call_user_func($this->additionalButtonsHtml) : $this->additionalButtonsHtml;
        $altActions = is_callable($this->altActions) ? call_user_func($this->altActions) : $this->altActions;
        $notice = is_callable($this->noticeHtml) ? call_user_func($this->noticeHtml) : $this->noticeHtml;
        $content = is_callable($this->contentHtml) ? call_user_func($this->contentHtml) : ($this->contentHtml ?? '');
        $sidebar = is_callable($this->metaSidebarHtml) ? call_user_func($this->metaSidebarHtml) : $this->metaSidebarHtml;
        $pageSidebar = is_callable($this->pageSidebarHtml) ? call_user_func($this->pageSidebarHtml) : $this->pageSidebarHtml;
        $errorSummary = is_callable($this->errorSummary) ? call_user_func($this->errorSummary) : $this->errorSummary;

        if ($this->action) {
            $content .= Html::actionInput($this->action, [
                'class' => 'action-input',
            ]);

            if ($this->redirectUrl) {
                $content .= Html::redirectInput($this->redirectUrl);
            }
        }

        // If this is a preview request and `useIframeResizer` is enabled, register the iframe resizer script
        if (
            $request->input('x-craft-live-preview') !== null &&
            Cms::config()->useIframeResizer
        ) {
            app(InternalAssetRegistry::class)->register(ContentWindowAsset::class);
        }

        $templateProps = [
            'docTitle' => $docTitle,
            'title' => $this->title,
            'selectedSubnavItem' => $this->selectedSubnavItem,
            'crumbs' => $crumbs,
            'contextMenu' => $this->contextMenu(),
            'toolbar' => $toolbar,
            'actionMenuItems' => $this->actionMenuItemProps(),
            'actionMenu' => $this->actionMenu(config: [
                'hiddenLabel' => t('Actions'),
                'buttonAttributes' => [
                    'id' => 'action-btn',
                    'class' => ['action-btn', 'hairline-dark', 'm'],
                    'title' => t('Actions'),
                ],
            ]),
            'submitButtonLabel' => $this->submitButtonLabel,
            'additionalButtons' => $addlButtons,
            'tabs' => $this->tabs,
            'subnav' => $this->subnav,
            'fullPageForm' => $isForm,
            'mainAttributes' => $this->mainAttributes,
            'mainFormAttributes' => $this->formAttributes,
            'redirectUrl' => $this->redirectUrl ? Crypt::encrypt($this->redirectUrl) : null,
            'formActions' => array_map(function (array $action): array {
                if (isset($action['redirect'])) {
                    $action['redirect'] = Crypt::encrypt($action['redirect']);
                }

                return $action;
            }, $altActions ?? []),
            'saveShortcutRedirect' => $this->saveShortcutRedirectUrl,
            'contentNotice' => $notice,
            'content' => $content,
            'details' => $sidebar,
            'sidebar' => $pageSidebar,
            'errorSummary' => $errorSummary,
        ];

        if ($this->inertiaPage) {
            if ($this->subnav === null) {
                unset($templateProps['subnav']);
            }

            return Inertia::render($this->inertiaPage, $this->inertiaProps)
                ->with($templateProps)
                ->with($this->screenProps('page', withAssets: $request->inertia()))
                ->toResponse($request);
        }

        // Render and return the template
        return response(pageTemplate(
            '_layouts/cp',
            $templateProps,
            TemplateMode::Cp
        ));
    }

    private function contextMenu(?string $namespace = null): ?string
    {
        return $this->menu($this->contextMenuItems, [
            'id' => 'context-menu',
            'class' => 'padded',
            'autoLabel' => true,
            'hiddenLabel' => t('Select context'),
        ], $namespace);
    }

    /** @param array<string, mixed> $config */
    private function actionMenu(bool $withDestructive = true, array $config = [], ?string $namespace = null): ?string
    {
        $itemsFactory = $this->actionMenuItemsFactory($withDestructive);

        if ($itemsFactory === null) {
            return null;
        }

        return $this->menu($itemsFactory, $config + [
            'id' => 'action-menu',
        ], $namespace);
    }

    /** @return list<array<string, mixed>>|null */
    private function actionMenuItemProps(bool $withDestructive = true): ?array
    {
        return $this->menuItems($this->actionMenuItemsFactory($withDestructive));
    }

    private function actionMenuItemsFactory(bool $withDestructive): ?callable
    {
        if ($this->actionMenuItems === null) {
            return null;
        }

        if ($withDestructive) {
            return $this->actionMenuItems;
        }

        return fn () => array_filter(
            call_user_func($this->actionMenuItems),
            fn (array $item) => ! ($item['destructive'] ?? false),
        );
    }

    /** @param array<string, mixed> $config */
    private function menu(?callable $itemsFactory, array $config, ?string $namespace): ?string
    {
        if ($itemsFactory === null) {
            return null;
        }

        $render = function () use ($itemsFactory, $config): ?string {
            $items = $this->menuItems($itemsFactory);

            if (empty($items)) {
                return null;
            }

            return app(MenuHtml::class)->disclosureMenu($items, $config);
        };

        if ($namespace) {
            return InputNamespace::namespaceInputs($render, $namespace);
        }

        return $render();
    }

    /** @return list<array<string, mixed>>|null */
    private function menuItems(?callable $itemsFactory): ?array
    {
        if ($itemsFactory === null) {
            return null;
        }

        $items = app(MenuHtml::class)->disclosureMenuItems($itemsFactory() ?? []);

        if (empty($items)) {
            return null;
        }

        return $items;
    }
}
