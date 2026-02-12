<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Responses;

use Craft;
use craft\helpers\Cp;
use craft\helpers\UrlHelper;
use craft\web\assets\iframeresizer\ContentWindowAsset;
use craft\web\View;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Site\Data\Site;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Str;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Traits\Conditionable;
use Stringable;
use Symfony\Component\HttpFoundation\Response;

use function CraftCms\Cms\t;

final class CpScreenResponse implements Responsable
{
    use Conditionable;

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
     * @var Site|null The site that should be displayed within the breadcrumbs.
     *
     * @see site()
     */
    public ?Site $site = null;

    /**
     * @var array<Site|array{site:Site,status?:string}>|null The sites that should be selectable by the site breadcrumb menu.
     *
     * @see selectableSites()
     */
    public ?array $selectableSites = null;

    /**
     * @var array|callable|null Breadcrumbs.
     *
     * This will only be used by full-page screens.
     *
     * @see crumbs()
     * @see addCrumb()
     */
    public $crumbs;

    /**
     * @var array Tabs.
     *
     * @see tabs()
     * @see addTab()
     */
    public array $tabs = [];

    /**
     * @var string|null Class that should be added to the slideout body.
     */
    public ?string $slideoutBodyClass = null;

    /**
     * @var array Custom attributes to add to the `<main>` tag.
     *
     * See [[\yii\helpers\BaseHtml::renderTagAttributes()]] for supported attribute syntaxes.
     *
     * This will only be used by full-page screens.
     *
     * @see mainAttributes()
     */
    public array $mainAttributes = [];

    /**
     * @var array Custom attributes to add to the `<form>` tag.
     *
     * See [[\yii\helpers\BaseHtml::renderTagAttributes()]] for supported attribute syntaxes.
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
     * @var array|callable|null Alternate form actions.
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
     * @var string|callable|null Toolbar HTML
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
     * @var string|callable|null Additional buttons’ HTML.
     *
     * This will only be used by full-page screens.
     *
     * @see additionalButtonsHtml()
     * @see additionalButtonsTemplate()
     */
    public $additionalButtonsHtml;

    /**
     * @var string|callable|null The content HTML.
     *
     * @see contentHtml()
     * @see contentTemplate()
     */
    public $contentHtml;

    /**
     * @var string|callable|null The right-hand meta sidebar HTML.
     *
     * @see metaSidebarHtml()
     * @see metaSidebarTemplate()
     */
    public $metaSidebarHtml;

    /**
     * @var string|callable|null The left-hand page sidebar HTML (only used by full-page screens).
     *
     * @see pageSidebarHtml()
     * @see pageSidebarTemplate()
     */
    public $pageSidebarHtml;

    /**
     * @var string|callable|null The content notice HTML.
     *
     * @see noticeHtml()
     * @see noticeTemplate()
     */
    public $noticeHtml;

    /**
     * @var string|callable|null The errors summary HTML (DEV-212).
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
     * Breadcrumbs should be defined by arrays with the following keys:
     *
     * - `label` – The breadcrumb label, to be HTML-encoded
     * - `url` – The URL that the breadcrumb should link to
     * - `icon` – The icon which should be displayed beside the label
     * - `menu` – The menu items which should be displayed alongside the breadcrumb
     *   (see [[\craft\helpers\Cp::disclosureMenu()]] for documentation on supported item properties)
     * - `current` – Whether the breadcrumb represents the current page
     *
     * This will only be used by full-page screens.
     */
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
    public function addCrumb(string $label, string $url): self
    {
        if (! is_array($this->crumbs)) {
            $this->crumbs = [];
        }

        $this->crumbs[] = [
            'label' => $label,
            'url' => UrlHelper::cpUrl($url),
        ];

        return $this;
    }

    /**
     * Sets the site that should be displayed within the breadcrumbs.
     */
    public function site(?Site $value): self
    {
        $this->site = $value;

        return $this;
    }

    /**
     * Sets the sites that should be selectable by the site breadcrumb menu.
     *
     * @param  array<Site|array{site:Site,status?:string}>|null  $value
     */
    public function selectableSites(?array $value): self
    {
        $this->selectableSites = $value;

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
    public function tabs(array $value): self
    {
        $this->tabs = $value;

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
     * See [[\yii\helpers\BaseHtml::renderTagAttributes()]] for supported attribute syntaxes.
     *
     * This will only be used by full-page screens.
     */
    public function mainAttributes(array $value): self
    {
        $this->mainAttributes = $value;

        return $this;
    }

    /**
     * Sets custom attributes that should be added to the `<form>` tag.
     *
     * See [[\yii\helpers\BaseHtml::renderTagAttributes()]] for supported attribute syntaxes.
     */
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
     * See [[\craft\helpers\Cp::disclosureMenu()]] for documentation on supported item properties.
     */
    public function contextMenuItems(?callable $value): self
    {
        $this->contextMenuItems = $value;

        return $this;
    }

    /**
     * Sets the toolbar HTML.
     */
    public function toolbarHtml(callable|string|null $value): self
    {
        $this->toolbarHtml = $value;

        return $this;
    }

    /**
     * Sets a template that should be used to render the toolbar HTML.
     */
    public function toolbarTemplate(string $template, array $variables = []): self
    {
        return $this->toolbarHtml(
            fn () => Craft::$app->getView()->renderTemplate($template, $variables, View::TEMPLATE_MODE_CP),
        );
    }

    /**
     * Sets the action menu items.
     *
     * See [[\craft\helpers\Cp::disclosureMenu()]] for documentation on supported item properties.
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
    public function additionalButtonsTemplate(string $template, array $variables = []): self
    {
        return $this->additionalButtonsHtml(
            fn () => Craft::$app->getView()->renderTemplate($template, $variables, View::TEMPLATE_MODE_CP),
        );
    }

    /**
     * Sets the content HTML.
     */
    public function contentHtml(callable|string|null $value): self
    {
        $this->contentHtml = $value;

        return $this;
    }

    /**
     * Sets a template that should be used to render the content HTML.
     */
    public function contentTemplate(string $template, array $variables = []): self
    {
        return $this->contentHtml(
            fn () => Craft::$app->getView()->renderTemplate($template, $variables, View::TEMPLATE_MODE_CP),
        );
    }

    /**
     * Sets the right-hand meta sidebar HTML.
     */
    public function metaSidebarHtml(callable|string|null $value): self
    {
        $this->metaSidebarHtml = $value;

        return $this;
    }

    /**
     * Sets a template that should be used to render the right-hand meta sidebar HTML.
     */
    public function metaSidebarTemplate(string $template, array $variables = []): self
    {
        return $this->metaSidebarHtml(
            fn () => Craft::$app->getView()->renderTemplate($template, $variables, View::TEMPLATE_MODE_CP),
        );
    }

    /**
     * Sets the left-hand page sidebar HTML (only used by full-page screens).
     */
    public function pageSidebarHtml(callable|string|null $value): self
    {
        $this->pageSidebarHtml = $value;

        return $this;
    }

    /**
     * Sets a template that should be used to render the left-hand page sidebar HTML (only used by full-page screens).
     */
    public function pageSidebarTemplate(string $template, array $variables = []): self
    {
        return $this->pageSidebarHtml(
            fn () => Craft::$app->getView()->renderTemplate($template, $variables, View::TEMPLATE_MODE_CP),
        );
    }

    /**
     * Sets the content notice HTML.
     */
    public function noticeHtml(callable|string|null $value): self
    {
        $this->noticeHtml = $value;

        return $this;
    }

    /**
     * Sets a template that should be used to render the content notice HTML.
     */
    public function noticeTemplate(string $template, array $variables = []): self
    {
        return $this->noticeHtml(
            fn () => Craft::$app->getView()->renderTemplate($template, $variables, View::TEMPLATE_MODE_CP),
        );
    }

    /**
     * Sets the errors summary HTML.
     */
    public function errorSummary(callable|string|null $value): self
    {
        $this->errorSummary = $value;

        return $this;
    }

    /**
     * Sets a template that should be used to render the errors summary HTML.
     */
    public function errorSummaryTemplate(string $template, array $variables = []): self
    {
        return $this->errorSummary(
            fn () => Craft::$app->getView()->renderTemplate($template, $variables, View::TEMPLATE_MODE_CP),
        );
    }

    public function toResponse($request): Response
    {
        if ($request->wantsJson()) {
            return $this->jsonResponse($request);
        }

        return $this->response($request);
    }

    private function jsonResponse(Request $request): JsonResponse
    {
        $namespace = Str::random(10);
        $view = Craft::$app->getView();

        if ($this->prepareScreen) {
            $containerId = $request->header('X-Craft-Container-Id');

            abort_unless((bool) $containerId, 400, 'Request missing the X-Craft-Container-Id header.');

            $view->setNamespace($namespace);
            call_user_func($this->prepareScreen, $this, $containerId);
            $view->setNamespace(null);
        }

        $extraToolbarItems = is_callable($this->toolbarHtml) ? call_user_func($this->toolbarHtml) : $this->toolbarHtml;
        $notice = $this->noticeHtml ? $view->namespaceInputs($this->noticeHtml, $namespace) : null;

        $tabs = count($this->tabs) > 1 ? $view->namespaceInputs(fn () => $view->renderTemplate('_includes/tabs.twig', [
            'tabs' => $this->tabs,
        ], View::TEMPLATE_MODE_CP), $namespace) : null;

        $content = $view->namespaceInputs(function () {
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

        $sidebar = $this->metaSidebarHtml ? $view->namespaceInputs($this->metaSidebarHtml, $namespace) : null;
        $errorSummary = $this->errorSummary ? $view->namespaceInputs($this->errorSummary, $namespace) : null;

        return new JsonResponse([
            'editUrl' => $this->editUrl ? UrlHelper::cpUrl($this->editUrl) : null,
            'namespace' => $namespace,
            'title' => $this->title,
            'notice' => $notice,
            'tabs' => $tabs,
            'bodyClass' => $this->slideoutBodyClass,
            'formAttributes' => $this->formAttributes,
            'action' => $this->action,
            'extraToolbarItems' => $extraToolbarItems,
            'submitButtonLabel' => $this->submitButtonLabel,
            'actionMenu' => $this->actionMenu(withDestructive: false, config: [
                'withButton' => false,
            ], namespace: $namespace),
            'content' => $content,
            'sidebar' => $sidebar,
            'errorSummary' => $errorSummary,
            'headHtml' => $view->getHeadHtml(),
            'bodyHtml' => $view->getBodyHtml(),
            'deltaNames' => $view->getDeltaNames(),
            'initialDeltaValues' => $view->getInitialDeltaValues(),
        ]);
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

        if (isset($this->site) && Sites::isMultiSite()) {
            array_unshift($crumbs, [
                'id' => 'site-crumb',
                'icon' => Cp::earthIcon(),
                'label' => t($this->site->getName(), category: 'site'),
                'menu' => [
                    'label' => t('Select site'),
                    'items' => ! empty($this->selectableSites)
                        ? Cp::siteMenuItems($this->selectableSites, $this->site, [
                            'includeOmittedSites' => true,
                        ])
                        : null,
                ],
            ]);
        }

        if ($this->action) {
            $content .= Html::actionInput($this->action, [
                'class' => 'action-input',
            ]);

            if ($this->redirectUrl) {
                $content .= Html::redirectInput($this->redirectUrl);
            }
        }

        $view = Craft::$app->getView();

        // If this is a preview request and `useIframeResizer` is enabled, register the iframe resizer script
        if (
            $request->input('x-craft-live-preview') !== null &&
            Cms::config()->useIframeResizer
        ) {
            $view->registerAssetBundle(ContentWindowAsset::class);
        }

        // Render and return the template
        return response($view->renderPageTemplate(
            '_layouts/cp',
            [
                'docTitle' => $docTitle,
                'title' => $this->title,
                'selectedSubnavItem' => $this->selectedSubnavItem,
                'crumbs' => array_map(function (array $crumb): array {
                    if (isset($crumb['url'])) {
                        $crumb['url'] = UrlHelper::cpUrl($crumb['url']);
                    }

                    return $crumb;
                }, $crumbs ?? []),
                'contextMenu' => $this->contextMenu(),
                'toolbar' => $toolbar,
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
                'fullPageForm' => $isForm,
                'mainAttributes' => $this->mainAttributes,
                'mainFormAttributes' => $this->formAttributes,
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
            ],
            View::TEMPLATE_MODE_CP
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

    private function actionMenu(bool $withDestructive = true, array $config = [], ?string $namespace = null): ?string
    {
        if ($this->actionMenuItems === null) {
            return null;
        }

        if ($withDestructive) {
            $itemsFactory = $this->actionMenuItems;
        } else {
            $itemsFactory = fn () => array_filter(
                call_user_func($this->actionMenuItems),
                fn (array $item) => ! ($item['destructive'] ?? false),
            );
        }

        return $this->menu($itemsFactory, $config + [
            'id' => 'action-menu',
        ], $namespace);
    }

    private function menu(?callable $itemsFactory, array $config, ?string $namespace): ?string
    {
        if ($itemsFactory === null) {
            return null;
        }

        $render = function () use ($itemsFactory, $config): ?string {
            $items = Cp::normalizeMenuItems($itemsFactory() ?? []);

            if (empty($items)) {
                return null;
            }

            return Cp::disclosureMenu($items, $config);
        };

        if ($namespace) {
            return Craft::$app->getView()->namespaceInputs($render, $namespace);
        }

        return $render();
    }
}
