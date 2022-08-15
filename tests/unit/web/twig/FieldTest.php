<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\web\twig;

use Codeception\Test\Unit;
use Craft;
use craft\test\TestCase;
use craft\web\View;

/**
 * Unit tests for the Various functions in the Extension class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.7.24
 */
class FieldTest extends TestCase
{
    /**
     * @var View
     */
    protected View $view;

    /**
     *
     */
    public function testBlocks(): void
    {
        $template = <<<TWIG
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

        $html = $this->view->renderString($template, [], View::TEMPLATE_MODE_CP);
        $this->assertStringContainsString('<div id="foo-field" class="field" data-attribute="foo" data-foo="test">', $html);
        $this->assertStringContainsString('TEST HEADING', $html);
        $this->assertStringContainsString('<label id="label" for="foo">TEST LABEL</label>', $html);
        $this->assertStringContainsString('<div id="foo-instructions" class="instructions"><p>TEST INSTRUCTIONS</p>', $html);
        $this->assertStringContainsString('<p id="foo-tip" class="notice has-icon"><span class="icon" aria-hidden="true"></span><span class="visually-hidden">Tip: </span><span>TEST TIP</span></p>', $html);
        $this->assertStringContainsString('<p id="foo-warning" class="warning has-icon"><span class="icon" aria-hidden="true"></span><span class="visually-hidden">Warning: </span><span>TEST WARNING</span></p>', $html);
        $this->assertStringContainsString('<input name="foo">', $html);
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
