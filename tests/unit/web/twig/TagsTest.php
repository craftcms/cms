<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\web\twig;

use Craft;
use craft\test\TestCase;
use UnitTester;

/**
 * Unit tests for Craft's Twig tags.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.12.4
 */
class TagsTest extends TestCase
{
    /**
     * @var UnitTester
     */
    protected UnitTester $tester;

    public function testTagsCompileWithoutTwigDeprecations(): void
    {
        $template = <<<'TWIG'
{% cache %}a{% endcache %}
{% css %}a{% endcss %}
{% dd x %}
{% deprecated 'a' %}
{% dump x %}
{% exit 404 %}
{% expires in 3 days %}
{% header 'X-Foo: bar' %}
{% hook 'a' %}
{% html %}a{% endhtml %}
{% js %}a{% endjs %}
{% namespace 'a' %}a{% endnamespace %}
{% nav e in entries %}{{ e }}{% ifchildren %}<ul>{% children %}</ul>{% endifchildren %}{% endnav %}
{% paginate query as pageInfo, items %}
{% redirect 'a' %}
{% render x %}
{% requireAdmin %}
{% requireEdition 1 %}
{% requireGuest %}
{% requireLogin %}
{% requirePermission 'a' %}
{% script %}a{% endscript %}
{% switch x %}{% case 1 %}a{% default %}b{% endswitch %}
{% tag 'p' %}a{% endtag %}
TWIG;

        $deprecations = [];
        set_error_handler(function(int $level, string $message) use (&$deprecations) {
            if (str_starts_with($message, 'Since twig/twig ')) {
                $deprecations[] = $message;
            }
            return true;
        }, E_USER_DEPRECATED);

        try {
            Craft::$app->getView()->getTwig()->createTemplate($template, uniqid('tags', true));
        } finally {
            restore_error_handler();
        }

        self::assertSame([], $deprecations);
    }

    public function testNestedNamespaceTags(): void
    {
        self::assertSame(
            '<input name="a[x]"><input name="a[b][y]"><input name="a[z]">',
            Craft::$app->getView()->renderString('{% namespace "a" %}<input name="x">{% namespace "b" %}<input name="y">{% endnamespace %}<input name="z">{% endnamespace %}'),
        );
    }

    public function testMacrosImportedInCapturingTagsStayAvailable(): void
    {
        self::assertSame(
            '<p>hi</p>|hi|hi|hi|hi',
            Craft::$app->getView()->renderString(<<<'TWIG'
{%- macro hi() %}hi{% endmacro -%}
{% tag 'p' %}{% import _self as a %}{{ a.hi() }}{% endtag %}|{{ a.hi() }}
{%- namespace 'n' %}{% import _self as b %}{% endnamespace %}|{{ b.hi() }}
{%- cache if false %}{% import _self as c %}{% endcache %}|{{ c.hi() }}
{%- css %}{% import _self as d %}{% endcss %}|{{ d.hi() }}
TWIG),
        );
    }
}
