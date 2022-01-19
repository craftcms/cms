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
     * @var array|null Breadcrumbs.
     * @see crumbs()
     * @see addCrumb()
     */
    public ?array $crumbs = null;

    /**
     * @var array|null Tabs.
     * @see tabs()
     * @see addTab()
     */
    public ?array $tabs = null;

    /**
     * @var string|null The form action
     * @see actionParam()
     */
    public ?string $actionParam = null;

    /**
     * @var string|null The `redirect` param.
     * @see redirect()
     */
    public ?string $redirectParam = null;

    /**
     * @var string|null The `redirect` param that should be used if the form is submitted with a keyboard shortcut
     * @see saveShortcutRedirect()
     */
    public ?string $saveShortcutRedirect = null;

    /**
     * @var callable|null Callable that returns the rendered content HTML
     * @see content()
     * @see contentTemplate()
     */
    public $content = null;

    /**
     * @var callable|null Callable that returns the rendered sidebar HTML
     * @see sidebar()
     * @see sidebarTemplate()
     */
    public $sidebar = null;

    /**
     * Sets the document title.
     *
     * @param string $value
     * @return Response|self
     */
    public function docTitle(string $value): Response
    {
        $this->docTitle = $value;
        return $this->owner;
    }

    /**
     * Sets the page title.
     *
     * @param string $value
     * @return Response|self
     */
    public function title(string $value): Response
    {
        $this->title = $value;
        return $this->owner;
    }

    /**
     * Sets the breadcrumbs.
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
     * @param string $value
     * @return Response|self
     */
    public function actionParam(string $value): Response
    {
        $this->actionParam = $value;
        return $this->owner;
    }

    /**
     * Sets the `redirect` param URL.
     *
     * @param string $value
     * @return Response|self
     */
    public function redirectParam(string $value): Response
    {
        $this->redirectParam = $value;
        return $this->owner;
    }

    /**
     * Sets the `redirect` param that should be used if the form is submitted with a keyboard shortcut.
     *
     * @param string $value
     * @return Response|self
     */
    public function saveShortcutRedirect(string $value): Response
    {
        $this->saveShortcutRedirect = $value;
        return $this->owner;
    }

    /**
     * Sets the callable that returns the rendered content HTML.
     *
     * @param callable $value
     * @return Response|self
     */
    public function content(callable $value): Response
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
     * Sets the callable that returns the rendered sidebar HTML.
     *
     * @param callable $value
     * @return Response|self
     */
    public function sidebar(callable $value): Response
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
