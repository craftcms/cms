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
use CraftCms\Cms\View\TemplateMode;
use function CraftCms\Cms\renderString;

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

        $html = renderString($template, [], TemplateMode::Cp);
        self::assertStringContainsString('<div class="field" id="foo-field" data-attribute="foo" data-foo="test">', $html);
        self::assertStringContainsString('TEST HEADING', $html);
        self::assertStringContainsString('<label id="label" for="foo">TEST LABEL</label>', $html);
        self::assertStringContainsString('<div id="foo-instructions" class="instructions"><p>TEST INSTRUCTIONS</p>', $html);
        self::assertMatchesRegularExpression('/<craft-callout\s+id="foo-tip"\s+variant="info"[^>]*>.*TEST TIP.*<\/craft-callout>/s', $html);
        self::assertMatchesRegularExpression('/<craft-callout\s+id="foo-warning"\s+variant="warning"[^>]*>.*TEST WARNING.*<\/craft-callout>/s', $html);
        self::assertStringContainsString('<input name="foo">', $html);
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
