<?php

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\web\twig;

use Codeception\Test\Unit;
use Craft;
use craft\test\TestCase;
use craft\web\View;
use CraftCms\Cms\View\TemplateMode;

use function CraftCms\Cms\renderString;

/**
 * Unit tests for the Various functions in the Extension class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 *
 * @since 3.7.24
 */
class FieldTest extends TestCase
{
    protected View $view;

    public function test_blocks(): void
    {
        $template = <<<'TWIG'
{% embed '_includes/forms/field' with {
  id: 'foo',
  labelId: 'label',
} %}
  {% block attr %}data-foo="test"{% endblock %}
  {% block heading %}TEST HEADING{{ parent() }}{% endblock %}
  {% block label %}TEST LABEL{% endblock %}
  {% block instructions %}<p>TEST INSTRUCTIONS</p>{% endblock %}
  {% block tip %}TEST TIP{% endblock %}
  {% block warning %}TEST WARNING{% endblock %}
  {% block input %}<input name="foo">{% endblock %}
{% endembed %}
TWIG;

        $html = renderString($template, [], TemplateMode::Cp);
        self::assertStringContainsString('<craft-field class="field" data-attribute="foo" data-foo="test" id="foo-field" label="TEST LABEL" orientation="ltr">', $html);
        self::assertStringContainsString('<span slot="heading-prefix">TEST HEADING</span>', $html);
        self::assertStringContainsString('<div slot="help-text"><p>TEST INSTRUCTIONS</p>', $html);
        self::assertStringContainsString('<span slot="tip">TEST TIP</span>', $html);
        self::assertStringContainsString('<span slot="warning">TEST WARNING</span>', $html);
        self::assertStringContainsString('<input name="foo" slot="input">', $html);
    }

    /**
     * {@inheritdoc}
     */
    protected function _before(): void
    {
        parent::_before();
        $this->view = Craft::$app->getView();
    }
}
