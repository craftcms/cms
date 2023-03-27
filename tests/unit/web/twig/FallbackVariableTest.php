<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\web\twig;

use Craft;
use craft\test\TestCase;
use craft\web\View;

/**
 * Unit tests for fallback Twig variables
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.5.0
 */
class FallbackVariableTest extends TestCase
{
    /**
     * @var View
     */
    protected View $view;

    public function testGlobalVariables(): void
    {
        $template = <<<TWIG
{{ dump(_context|keys) }}
TWIG;
        self::assertStringNotContainsString('foobar', $this->view->renderString($template, templateMode: View::TEMPLATE_MODE_SITE));

        $template = <<<TWIG
{% set global foobar = 'foobar' %}
{{ dump(_context|keys) }}
TWIG;
        self::assertStringNotContainsString('foobar', $this->view->renderString($template, templateMode: View::TEMPLATE_MODE_SITE));

        $template = <<<TWIG
{% set global foobar = 'foobar' %}
{{ foobar }}
TWIG;
        self::assertSame('foobar', $this->view->renderString($template, templateMode: View::TEMPLATE_MODE_SITE));

        $template = <<<TWIG
{% set global foobar, bazqux = 'foobar', 'bazqux' %}
{{ foobar }}, {{ bazqux }}
TWIG;
        self::assertSame('foobar, bazqux', $this->view->renderString($template, templateMode: View::TEMPLATE_MODE_SITE));

        $template = <<<TWIG
{% set global foobar %}foobar{% endset %}
{{ foobar }}
TWIG;
        self::assertSame('foobar', $this->view->renderString($template, templateMode: View::TEMPLATE_MODE_SITE));
    }

    /**
     * @inheritdoc
     */
    protected function _before(): void
    {
        parent::_before();

        $this->view = Craft::$app->getView();
    }
}
