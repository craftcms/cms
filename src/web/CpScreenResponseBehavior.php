<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web;

use Craft;
use craft\helpers\Html;
use craft\helpers\UrlHelper;
use yii\base\Behavior;

/**
 * Control panel screen response behavior.
 *
 * @property Response $owner
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class CpScreenResponseBehavior extends Behavior
{
    const NAME = 'cp-screen';

    /**
     * @var callable|null Callable that will be called before other properties are added to the screen.
     * @see prepareScreen()
     */
    public $prepareScreen = null;

    /**
     * @var string|null The document title. If null, [[title]] will be used.
     * @see docTitle()
     */
    public ?string $docTitle = null;

    /**
     * @var string|null The page title.
     * @see title()
     */
    public ?string $title = null;

    /**
     * @var array Breadcrumbs.
     * @see crumbs()
     * @see addCrumb()
     */
    public array $crumbs = [];

    /**
     * @var array Tabs.
     * @see tabs()
     * @see addTab()
     */
    public array $tabs = [];

    /**
     * @var string|null The form action
     * @see action()
     */
    public ?string $action = null;

    /**
     * @var string|null The URL the form should redirect to after posting
     * @see redirectUrl()
     */
    public ?string $redirectUrl = null;

    /**
     * @var string|null The URL the form should redirect to after posting, if submitted via the
     * <kbd>Ctrl</kbd><kbd>Command</kbd> + <kbd>S</kbd> keyboard shortcut.
     * @see saveShortcutRedirectUrl()
     */
    public ?string $saveShortcutRedirectUrl = null;

    /**
     * @var string|callable|null The content HTML
     * @see content()
     * @see contentTemplate()
     */
    public $content = null;

    /**
     * @var string|callable|null The sidebar HTML
     * @see sidebar()
     * @see sidebarTemplate()
     */
    public $sidebar = null;

    /**
     * Sets a callable that will be called before other properties are added to the screen.
     *
     * @param callable|null $value
     * @return Response|self
     */
    public function prepareScreen(?callable $value): Response
    {
        $this->prepareScreen = $value;
        return $this->owner;
    }

    /**
     * Sets the document title.
     *
     * @param string|null $value
     * @return Response|self
     */
    public function docTitle(?string $value): Response
    {
        $this->docTitle = $value;
        return $this->owner;
    }

    /**
     * Sets the page title.
     *
     * @param string|null $value
     * @return Response|self
     */
    public function title(?string $value): Response
    {
        $this->title = $value;
        return $this->owner;
    }

    /**
     * Sets the breadcrumbs.
     *
     * Each breadcrumb should be represented by a nested array with `label` and `url` keys.
     *
     * @param array $value
     * @return Response|self
     */
    public function crumbs(array $value): Response
    {
        $this->crumbs = array_map(function(array $crumb): array {
            $crumb['url'] = UrlHelper::cpUrl($crumb['url'] ?? '');
            return $crumb;
        }, $value);
        return $this->owner;
    }

    /**
     * Adds a breadcrumb.
     *
     * @param string $label
     * @param string $url
     * @return Response|self
     */
    public function addCrumb(string $label, string $url): Response
    {
        $this->crumbs[] = [
            'label' => $label,
            'url' => UrlHelper::cpUrl($url),
        ];
        return $this->owner;
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
     *
     * @param array $value
     * @return Response|self
     */
    public function tabs(array $value): Response
    {
        $this->tabs = $value;
        return $this->owner;
    }

    /**
     * Adds a tab.
     *
     * @param string $id
     * @param string $label
     * @param string $url
     * @param string|string[]|null $class
     * @param bool $visible
     * @return Response|self
     */
    public function addTab(string $id, string $label, string $url, $class = null, bool $visible = true): Response
    {
        $this->tabs[$id] = [
            'label' => $label,
            'url' => $url,
            'class' => Html::explodeClass($class),
            'visible' => $visible,
        ];
        return $this->owner;
    }

    /**
     * Sets the form action.
     *
     * @param string|null $value
     * @return Response|self
     */
    public function action(?string $value): Response
    {
        $this->action = $value;
        return $this->owner;
    }

    /**
     * Sets the URL the form should redirect to after posting
     *
     * @param string|null $value
     * @return Response|self
     */
    public function redirectUrl(?string $value): Response
    {
        $this->redirectUrl = $value;
        return $this->owner;
    }

    /**
     * Sets URL the form should redirect to after posting, if submitted via the
     * <kbd>Ctrl</kbd><kbd>Command</kbd> + <kbd>S</kbd> keyboard shortcut.
     *
     * @param string|null $value
     * @return Response|self
     */
    public function saveShortcutRedirectUrl(?string $value): Response
    {
        $this->saveShortcutRedirectUrl = $value;
        return $this->owner;
    }

    /**
     * Sets the content HTML.
     *
     * @param string|callable|null $value
     * @return Response|self
     */
    public function content($value): Response
    {
        $this->content = $value;
        return $this->owner;
    }

    /**
     * Sets a template that should be used to render the content HTML.
     *
     * @param string $template
     * @param array $variables
     * @return Response|self
     */
    public function contentTemplate(string $template, array $variables = []): Response
    {
        $this->content = fn() => Craft::$app->getView()->renderTemplate($template, $variables, View::TEMPLATE_MODE_CP);
        return $this->owner;
    }

    /**
     * Sets the sidebar HTML.
     *
     * @param string|callable|null $value
     * @return Response|self
     */
    public function sidebar($value): Response
    {
        $this->sidebar = $value;
        return $this->owner;
    }

    /**
     * Sets a template that should be used to render the sidebar HTML.
     *
     * @param string $template
     * @param array $variables
     * @return Response|self
     */
    public function sidebarTemplate(string $template, array $variables = []): Response
    {
        $this->sidebar = fn() => Craft::$app->getView()->renderTemplate($template, $variables, View::TEMPLATE_MODE_CP);
        return $this->owner;
    }
}
