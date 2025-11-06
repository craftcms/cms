<?php

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\web\twig;

use ArrayObject;
use Craft;
use craft\elements\Address;
use craft\elements\ElementCollection;
use craft\elements\Entry;
use craft\elements\User;
use craft\test\TestCase;
use craft\test\TestSetup;
use craft\web\View;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Element\Models\EntryType;
use CraftCms\Cms\Field\MissingField;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\FieldLayout\Models\FieldLayout;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Support\Facades\EntryTypes;
use crafttests\fixtures\FieldLayoutFixture;
use crafttests\fixtures\GlobalSetFixture;
use DateInterval;
use DateTime;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Session;
use Throwable;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use UnitTester;
use yii\base\ErrorException;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\base\NotSupportedException;
use yii\web\ServerErrorHttpException;
use function CraftCms\Cms\t;

/**
 * Unit tests for the Various functions in the Extension class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 *
 * @since 3.2
 */
class ExtensionTest extends TestCase
{
    protected View $view;

    /**
     * @var UnitTester
     */
    protected UnitTester $tester;

    /**
     * @throws LoaderError
     * @throws SyntaxError
     */
    public function test_globals(): void
    {
        // We want web for this part.
        Craft::$app->getRequest()->setIsConsoleRequest(false);
        $user = new User([
            'active' => true,
            'firstName' => 'John',
            'lastName' => 'Smith',
        ]);
        Craft::$app->getUser()->setIdentity($user);
        Craft::$app->getRequest()->setRawBody('This is a raw body');

        // Current user
        $this->testRenderResult(
            'John | Smith',
            '{{ currentUser.firstName }} | {{ currentUser.lastName }}'
        );

        // Craft variable - poke various calls.
        $this->testRenderResult(
            'John',
            '{{ craft.app.user.getIdentity().firstName }}'
        );

        $this->testRenderResult(
            'This is a raw body',
            '{{ craft.app.request.getRawBody() }}'
        );
    }

    /**
     * @throws LoaderError
     * @throws SyntaxError
     * @throws Exception
     */
    public function test_craft_system_globals(): void
    {
        Edition::set(Edition::Pro);
        $this->testRenderResult(
            implode(',', [Edition::Solo->value, Edition::Team->value, Edition::Pro->value]),
            '{{ [CraftSolo, CraftTeam, CraftPro]|join(",") }}',
            templateMode: View::TEMPLATE_MODE_CP,
        );
    }

    /**
     * @throws LoaderError
     * @throws SyntaxError
     */
    public function test_globals_with_uninstalled_craft(): void
    {
        $installed = Craft::$app->getIsInstalled();
        Craft::$app->setIsInstalled(false);
        $this->testRenderResult(
            ' |  |  | ',
            '{{ systemName }} | {{ currentSite }} | {{ siteName }} | {{ siteUrl }}'
        );
        Craft::$app->setIsInstalled($installed);
    }

    /**
     * @throws LoaderError
     * @throws SyntaxError
     * @throws ErrorException
     * @throws Exception
     * @throws NotSupportedException
     * @throws ServerErrorHttpException
     */
    public function test_site_globals(): void
    {
        app(ProjectConfig::class)->set('system.name', 'Im a test system');
        $this->testRenderResult(
            'Im a test system | defaultSite Craft test site ' . TestSetup::SITE_URL,
            '{{ systemName }} | {{ currentSite.handle }} {{ currentSite }} {{ siteUrl }}'
        );
    }

    /**
     * @throws LoaderError
     * @throws SyntaxError
     * @throws Throwable
     */
    public function test_element_globals(): void
    {
        $this->tester->haveFixtures([
            'globals' => [
                'class' => GlobalSetFixture::class,
            ],
        ]);

        $this->testRenderResult(
            'A global set | A different global set',
            '{{ aGlobalSet }} | {{ aDifferentGlobalSet }}'
        );
    }

    public function test_instance_of_test(): void
    {
        $this->testRenderResult(
            'yes',
            '{{ foo is instance of(class) ? "yes" : "no" }}',
            [
                'foo' => new PlainText(),
                'class' => PlainText::class,
            ]
        );
        $this->testRenderResult(
            'no',
            '{{ foo is instance of(class) ? "yes" : "no" }}',
            [
                'foo' => new PlainText(),
                'class' => 'foo\\bar\\Baz',
            ]
        );
    }

    public function test_missing_test(): void
    {
        $this->testRenderResult(
            'yes',
            '{{ foo is missing ? "yes" : "no" }}',
            [
                'foo' => new MissingField(),
            ]
        );
        $this->testRenderResult(
            'no',
            '{{ foo is missing ? "yes" : "no" }}',
            [
                'foo' => new PlainText(),
            ]
        );
    }

    public function test_translate_filter(): void
    {
        $this->markTestSkipped('Move test to Laravel');

        $this->testRenderResult(
            'Translated message',
            '{{ "Source message"|t("site") }}'
        );
        $this->testRenderResult(
            'Translated message with foo',
            '{{ "Source message with {var}"|t("site", {var: myVar}) }}',
            [
                'myVar' => 'foo',
            ]
        );

        // 'site' category is optional
        $this->testRenderResult(
            'Translated message',
            '{{ "Source message"|t }}'
        );
        $this->testRenderResult(
            'Translated message with foo',
            '{{ "Source message with {var}"|t({var: myVar}) }}',
            [
                'myVar' => 'foo',
            ]
        );

        // |translate should swallow the InvalidConfigException here
        $this->testRenderResult(
            'Source message',
            '{{ "Source message"|t("invalidCategory") }}'
        );

        $this->expectException(InvalidConfigException::class);
        t('Source message', category: 'invalidCategory');
        $this->view->renderString('{{ "Source message"|t("invalidCategory") }}');
    }

    public function test_truncate_filter(): void
    {
        $this->testRenderResult(
            '',
            '{{ ""|truncate(8) }}'
        );
        $this->testRenderResult(
            'Test...',
            '{{ "Test foo bar"|truncate(8, "...") }}'
        );
    }

    public function test_ucfirst_filter(): void
    {
        $this->testRenderResult(
            'Foo bar',
            '{{ "foo bar"|ucfirst }}'
        );
    }

    /**
     * @deprecated
     */
    public function test_ucwords_filter(): void
    {
        $this->testRenderResult(
            'Foo Bar',
            '{{ "foo bar"|ucwords }}'
        );
    }

    public function test_lcfirst_filter(): void
    {
        $this->testRenderResult(
            'foo Bar',
            '{{ "Foo Bar"|lcfirst }}'
        );
    }

    public function test_kebab_filter(): void
    {
        $this->testRenderResult(
            'foo-bar',
            '{{ "foo bar"|kebab }}'
        );
    }

    public function test_camel_filter(): void
    {
        $this->testRenderResult(
            'fooBar',
            '{{ "foo bar"|camel }}'
        );
    }

    public function test_pascal_filter(): void
    {
        $this->testRenderResult(
            'FooBar',
            '{{ "foo bar"|pascal }}'
        );
    }

    public function test_snake_filter(): void
    {
        $this->testRenderResult(
            'foo_bar',
            '{{ "foo bar"|snake }}'
        );
    }

    public function test_json_encode_filter(): void
    {
        $this->testRenderResult(
            '{"foo":true}',
            '{{ myVar|json_encode }}',
            [
                'myVar' => ['foo' => true],
            ]
        );
    }

    /**
     * @throws LoaderError
     * @throws SyntaxError
     */
    public function test_without_filter(): void
    {
        $this->testRenderResult(
            'foo,bar',
            '{{ ["foo","bar","baz"]|without("baz")|join(",") }}'
        );
        $this->testRenderResult(
            'foo',
            '{{ ["foo","bar","baz"]|without(["bar","baz"])|join(",") }}'
        );
    }

    /**
     * @throws LoaderError
     * @throws SyntaxError
     */
    public function test_without_key_filter(): void
    {
        $this->testRenderResult(
            'foo,bar',
            '{{ {a:"foo",b:"bar",c:"baz"}|withoutKey("c")|join(",") }}'
        );
        $this->testRenderResult(
            'foo',
            '{{ {a:"foo",b:"bar",c:"baz"}|withoutKey(["b","c"])|join(",") }}'
        );
    }

    public function test_attr_filter(): void
    {
        $this->testRenderResult(
            '<p class="foo">Hey</p>',
            '{{ "<p>Hey</p>"|attr({class: "foo"}) }}'
        );

        // |attr should swallow the InvalidArgumentException here
        $this->testRenderResult(
            'Hey',
            '{{ "Hey"|attr({class: "foo"}) }}'
        );
    }

    public function test_base64_decode_filter(): void
    {
        $encoded = base64_encode('foo');
        $this->testRenderResult(
            'foo',
            "{{ '$encoded'|base64_decode }}",
        );
    }

    public function test_base64_encode_filter(): void
    {
        $this->testRenderResult(
            base64_encode('foo'),
            '{{ "foo"|base64_encode }}',
        );
    }

    public function test_parse_attr_filter(): void
    {
        $this->testRenderResult(
            '{"id":"foo","class":["bar","baz"]}',
            '{{ \'<p id="foo" class="bar baz">Hello</p>\'|parseAttr|json_encode }}'
        );

        // |parseAttr should swallow the InvalidArgumentException here
        $this->testRenderResult(
            '[]',
            '{{ "foo"|parseAttr|json_encode }}'
        );
    }

    public function test_parse_refs_filter(): void
    {
        $this->testRenderResult(
            TestSetup::USERNAME,
            '{{ "{user:1:username}"|parseRefs }}'
        );
    }

    public function test_append_filter(): void
    {
        $this->testRenderResult(
            '<p><span>foo</span><span>bar</span></p>',
            '{{ "<p><span>foo</span></p>"|append("<span>bar</span>") }}'
        );
        $this->testRenderResult(
            '<p><span>foo</span></p>',
            '{{ "<p><span>bar</span></p>"|append("<span>foo</span>", "replace") }}'
        );
    }

    public function test_prepend_filter(): void
    {
        $this->testRenderResult(
            '<p><span>foo</span><span>bar</span></p>',
            '{{ "<p><span>bar</span></p>"|prepend("<span>foo</span>") }}'
        );
        $this->testRenderResult(
            '<p><span>foo</span></p>',
            '{{ "<p><span>bar</span></p>"|prepend("<span>foo</span>", "replace") }}'
        );
    }

    public function test_purify_filter(): void
    {
        $this->testRenderResult(
            '<p>foo</p>',
            '{{ \'<p bad-attr="bad-value">foo</p>\'|purify }}'
        );
    }

    public function test_push_filter(): void
    {
        $this->testRenderResult(
            '["foo","bar","baz"]',
            '{{ ["foo"]|push("bar", "baz")|json_encode }}'
        );
    }

    public function test_unshift_filter(): void
    {
        $this->testRenderResult(
            '["foo","bar","baz"]',
            '{{ ["baz"]|unshift("foo", "bar")|json_encode }}'
        );
    }

    public function test_remove_class_filter(): void
    {
        $this->testRenderResult('<div>', '{{ \'<div class="foo">\'|removeClass("foo") }}');
        $this->testRenderResult('<div class="bar">', '{{ \'<div class="foo bar">\'|removeClass("foo") }}');
        $this->testRenderResult('<div class="baz">', '{{ \'<div class="foo bar baz">\'|removeClass(["foo", "bar"]) }}');
        $this->testRenderResult('foo', '{{ \'foo\'|removeClass("foo") }}');
    }

    public function test_replace_filter(): void
    {
        $this->testRenderResult(
            'qux quux corge',
            '{{ "foo bar baz"|replace({foo: "qux", bar: "quux", baz: "corge"}) }}'
        );

        $this->testRenderResult(
            'qux',
            '{{ "foo bar baz"|replace("/f.*z/", "qux") }}'
        );

        $this->testRenderResult(
            'foo qux baz',
            '{{ "foo bar baz"|replace("bar", "qux") }}'
        );

        $this->testRenderResult(
            'foo zar zazzy',
            '{{ "foo bar baz"|replace({"/b(\\\w+)/": "z$1", zaz: "zazzy"}) }}',
        );

        // https://github.com/craftcms/cms/issues/13618
        $this->testRenderResult(
            'qux',
            '{{ "https://foo.com/bar/baz/"|replace("/(http(s?):)?\\\/\\\/foo\\\.com\\\/bar\\\/baz\\\//", "qux") }}',
        );

        $this->testRenderResult(
            '/baz/bar/',
            '{{ "/foo/bar/"|replace({"/foo/": "baz"}, regex=true) }}',
        );

        $this->testRenderResult(
            'bazbar/',
            '{{ "/foo/bar/"|replace({"/foo/": "baz"}, regex=false) }}',
        );
    }

    public function test_date_filter(): void
    {
        // DateInterval
        $this->testRenderResult(
            '4 days',
            '{{ d|date("%d days") }}',
            [
                'd' => new DateInterval('P2Y4DT6H8M'),
            ]
        );

        $d = new DateTime('2021-01-20 10:00:00');

        // ICU format
        $this->testRenderResult(
            '2021-01-20',
            '{{ d|date("icu:YYYY-MM-dd") }}',
            compact('d')
        );

        // PHP format
        $this->testRenderResult(
            '2021-01-20',
            '{{ d|date("Y-m-d") }}',
            compact('d')
        );
        $this->testRenderResult(
            '2021-01-20',
            '{{ d|date("php:Y-m-d") }}',
            compact('d')
        );
    }

    public function test_time_filter(): void
    {
        $d = new DateTime('2021-01-20 10:00:00');

        // ICU format
        $this->testRenderResult(
            '10:00:00',
            '{{ d|time("icu:HH:mm:ss") }}',
            compact('d')
        );

        // PHP format
        $this->testRenderResult(
            '10:00:00',
            '{{ d|time("h:i:s") }}',
            compact('d')
        );
        $this->testRenderResult(
            '10:00:00',
            '{{ d|time("php:h:i:s") }}',
            compact('d')
        );
    }

    public function test_datetime_filter(): void
    {
        $d = new DateTime('2021-01-20 10:00:00');

        // ICU format
        $this->testRenderResult(
            '2021-01-20 10:00:00',
            '{{ d|datetime("icu:YYYY-MM-dd HH:mm:ss") }}',
            compact('d')
        );

        // PHP format
        $this->testRenderResult(
            '2021-01-20 10:00:00',
            '{{ d|datetime("Y-m-d h:i:s") }}',
            compact('d')
        );
        $this->testRenderResult(
            '2021-01-20 10:00:00',
            '{{ d|datetime("php:Y-m-d h:i:s") }}',
            compact('d')
        );
    }

    public function test_atom_filter(): void
    {
        $d = new DateTime();
        $this->testRenderResult(
            $d->format(DateTime::ATOM),
            '{{ d|atom }}',
            compact('d')
        );
    }

    public function test_rss_filter(): void
    {
        $d = new DateTime();
        $this->testRenderResult(
            $d->format(DateTime::RSS),
            '{{ d|rss }}',
            compact('d')
        );
    }

    public function test_httpdate_filter(): void
    {
        $d = new DateTime();
        $this->testRenderResult(
            $d->format(DateTime::RFC7231),
            '{{ d|httpdate }}',
            compact('d')
        );
    }

    public function test_encenc_filter(): void
    {
        $enc = $this->view->renderString('{{ "foo"|encenc }}');
        self::assertStringStartsWith('base64:', $enc);
    }

    public function test_filter_filter(): void
    {
        $this->testRenderResult(
            'foo bar baz',
            '{{ ["foo", "", "bar", "", "baz"]|filter|join(" ") }}'
        );

        $this->testRenderResult(
            'foo bar',
            '{{ ["foo", "bar", "baz"]|filter(i => i != "baz")|join(" ") }}'
        );
    }

    public function test_group_filter(): void
    {
        $this->testRenderResult(
            TestSetup::USERNAME,
            '{{ craft.users().id(1).all()|group("username")|keys|join(",") }}'
        );

        $this->testRenderResult(
            TestSetup::USERNAME,
            '{{ craft.users().id(1).all()|group(u => u.username)|keys|join(",") }}'
        );

        // invalid value
        self::expectException(RuntimeError::class);
        $this->view->renderString('{% do "foo"|group("bar") %}');
    }

    /**
     *
     */
    public function testHashFilter(): void
    {
        $this->testRenderResult(
            Craft::$app->getSecurity()->hashData('test'),
            '{{ "test"|hash }}'
        );

        $this->testRenderResult(
            '098f6bcd4621d373cade4e832627b4f6',
            '{{ "test"|hash("md5") }}'
        );

        $this->testRenderResult(
            '9f86d081884c7d659a2feaa0c55ad015a3bf4f1b2b0b822cd15d6c15b0f00a08',
            '{{ "test"|hash("sha256") }}'
        );
    }

    /**
     * @throws LoaderError
     * @throws SyntaxError
     */
    public function test_index_of_filter(): void
    {
        $array = new ArrayObject(['John', 'Smith']);

        $this->testRenderResult(
            '3',
            '{{ "Im a string"|indexOf("a") }}'
        );

        $this->testRenderResult(
            '1',
            '{{ [2, 3, 4, 5]|indexOf(3) }}'
        );

        $this->testRenderResult(
            '1',
            '{{ array|indexOf("Smith") }}',
            compact('array')
        );

        $this->testRenderResult(
            '-1',
            '{{ array|indexOf("Doe") }}',
            compact('array')
        );
    }

    public function test_literal_filter(): void
    {
        $this->testRenderResult(
            '\\*foo\\*',
            '{{ "*foo*"|literal }}'
        );
    }

    public function test_markdown_filter(): void
    {
        $this->testRenderResult(
            "<p><strong>Hello</strong></p>\n",
            '{{ "**Hello**"|md }}'
        );

        $this->testRenderResult(
            "<p><strong>Hello</strong></p>\n",
            '{{ "**Hello**"|markdown }}'
        );

        $this->testRenderResult(
            '<strong>Hello</strong>',
            '{{ "**Hello**"|md(inlineOnly=true) }}'
        );
    }

    /**
     * @throws LoaderError
     * @throws SyntaxError
     *
     * @dataProvider addressFilterDataProvider
     */
    public function test_address_filter(string $renderString, array $variables, string $expected): void
    {
        $this->testRenderResult($expected, $renderString, $variables);
    }

    public static function addressFilterDataProvider(): array
    {
        return [
            ['{{ myAddress|address }}', ['myAddress' => Craft::createObject(Address::class, ['config' => ['attributes' => ['addressLine1' => '1 Main Stree', 'postalCode' => '12345', 'countryCode' => 'US', 'administrativeArea' => 'OR']]])], '<p translate="no">
<span class="address-line1">1 Main Stree</span><br>
<span class="administrative-area">OR</span> <span class="postal-code">12345</span><br>
<span class="country">United States</span>
</p>'],
            ['{{ myAddress|address }}', ['myAddress' => null], ''],
        ];
    }

    public function test_merge_filter(): void
    {
        $this->testRenderResult(
            'foo bar baz',
            '{{ ["foo"]|merge(["bar", "baz"])|join(" ") }}'
        );

        $this->testRenderResult(
            '{"f":"foo","b":["baz"]}',
            '{{ {f: "foo", b: ["bar"]}|merge({b: ["baz"]})|json_encode }}'
        );

        $this->testRenderResult(
            '{"f":"foo","b":["bar","baz"]}',
            '{{ {f: "foo", b: ["bar"]}|merge({b: ["baz"]}, recursive=true)|json_encode }}'
        );
    }

    public function test_multisort_filter(): void
    {
        $this->testRenderResult(
            'bar baz foo',
            '{{ [{k:"foo"},{k:"bar"},{k:"baz"}]|multisort("k")|column("k")|join(" ") }}'
        );
    }

    public function test_currency_filter(): void
    {
        $this->testRenderResult(
            '',
            '{{ null|currency }}'
        );

        $this->testRenderResult(
            '$299.00',
            '{{ 299|currency }}'
        );

        $this->testRenderResult(
            '$299',
            '{{ 299|currency(stripZeros=true) }}'
        );

        // |currency should swallow the InvalidArgumentException here
        $this->testRenderResult(
            'foo',
            '{{ "foo"|currency }}'
        );
    }

    public function test_filesize_filter(): void
    {
        $this->testRenderResult(
            '',
            '{{ null|filesize }}'
        );

        $this->testRenderResult(
            '1 KB',
            '{{ 1000|filesize }}'
        );

        // |filesize should swallow the InvalidArgumentException here
        $this->testRenderResult(
            'foo',
            '{{ "foo"|filesize }}'
        );
    }

    public function test_number_filter(): void
    {
        $this->testRenderResult(
            '',
            '{{ null|number }}'
        );

        $this->testRenderResult(
            '1,000',
            '{{ 1000|number }}'
        );

        $this->testRenderResult(
            '1,000.00',
            '{{ 1000|number(decimals=2) }}'
        );

        // |number should swallow the InvalidArgumentException here
        $this->testRenderResult(
            'foo',
            '{{ "foo"|number }}'
        );
    }

    public function test_percentage_filter(): void
    {
        $this->testRenderResult(
            '',
            '{{ null|percentage }}'
        );

        $this->testRenderResult(
            '80%',
            '{{ 0.8|percentage }}'
        );

        $this->testRenderResult(
            '80.0%',
            '{{ 0.8|percentage(decimals=1) }}'
        );

        // |percentage should swallow the InvalidArgumentException here
        $this->testRenderResult(
            'foo',
            '{{ "foo"|percentage }}'
        );
    }

    public function test_widont_filter(): void
    {
        $this->testRenderResult('foo bar&nbsp;baz', '{{ "foo bar baz"|widont }}');
    }

    public function test_clone_function(): void
    {
        $this->testRenderResult(
            'yes',
            '{% set q2 = clone(q) %}{{ q2.sectionId == q.sectionId and q2 is not same as(q) ? "yes" : "no" }}',
            [
                'q' => Entry::find()->sectionId(10),
            ]
        );
    }

    public function test_encode_url_function(): void
    {
        $this->testRenderResult(
            'https://domain/fr/offices/gen%C3%AAve',
            '{{ encodeUrl("https://domain/fr/offices/genêve") }}',
        );
    }

    public function test_expression_function(): void
    {
        $this->testRenderResult(
            'Im an expression | var | Im an expression',
            '{% set expression =  expression("Im an expression", ["var"]) %}{{ expression }} | {{ expression.params[0] }} | {{ expression.expression }}'
        );
    }

    public function test_field_value_sql_function(): void
    {
        $this->tester->haveFixtures([
            'fieldLayouts' => FieldLayoutFixture::class,
        ]);

        EntryType::whereHandle('test1')->update(['fieldLayoutId' => FieldLayout::whereUid('field-layout-1002----------------uid')->first()->id]);

        $entryType = EntryTypes::getEntryTypeByHandle('test1');
        $field = $entryType->getFieldLayout()->getFieldByHandle('plainTextField');
        $valueSql = $field->getValueSql();
        $this->testRenderResult(
            $valueSql,
            '{{ fieldValueSql(entryType(\'test1\'), \'plainTextField\') }}'
        );
    }

    public function test_gql_function(): void
    {
        $this->testRenderResult(
            '{"data":{"ping":"pong"}}',
            '{{ gql("{ping}")|json_encode }}'
        );
    }

    public function test_plugin_function(): void
    {
        $this->testRenderResult(
            'invalid',
            '{{ plugin("no-a-real-plugin") is same as(null) ? "invalid" }}'
        );
    }

    /**
     * @throws LoaderError
     * @throws SyntaxError
     */
    public function test_shuffle_function(): void
    {
        $array = [
            0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm',
            'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z',
        ];

        $this->testRenderResult(
            'yes',
            '{{ array != shuffle(array) or array != shuffle(array) ? "yes" : "no" }}',
            compact('array')
        );

        $this->testRenderResult(
            'yes',
            '{{ array != shuffle(array) or array != shuffle(array) ? "yes" : "no" }}',
            [
                'array' => new ArrayObject($array),
            ]
        );
    }

    public function test_svg_function(): void
    {
        $path = dirname(__DIR__, 3) . '/_data/assets/files/craft-logo.svg';
        $contents = file_get_contents($path);

        $svg = $this->view->renderString('{{ svg(path) }}', compact('path'));
        self::assertStringStartsWith('<svg', $svg);
        self::assertStringContainsString('id="Symbols"', $svg);

        $svg = $this->view->renderString('{{ svg(contents) }}', compact('contents'));
        self::assertStringStartsWith('<svg', $svg);
        self::assertRegExp('/id="\w+\-Symbols"/', $svg);

        $svg = $this->view->renderString('{{ svg(contents, namespace=false) }}', compact('contents'));
        self::assertStringStartsWith('<svg', $svg);
        self::assertStringContainsString('id="Symbols"', $svg);

        // deprecated
        $svg = $this->view->renderString('{{ svg(contents, class="foobar") }}', compact('contents'));
        self::assertStringContainsString('class="foobar"', $svg);
    }

    public function test_tag_function(): void
    {
        $this->testRenderResult(
            '<p class="foo">Hello</p>',
            '{{ tag("p", {text: "Hello", class: "foo"}) }}'
        );

        $this->testRenderResult(
            '<p>&lt;script&gt;alert(\'Hello\');&lt;/script&gt;</p>',
            '{{ tag("p", {text: "<script>alert(\'Hello\');</script>"}) }}'
        );

        $this->testRenderResult(
            '<p><script>alert(\'Hello\');</script></p>',
            '{{ tag("p", {html: "<script>alert(\'Hello\');</script>"}) }}'
        );
    }

    /**
     * @throws LoaderError
     * @throws SyntaxError
     */
    public function test_csrf_input_function(): void
    {
        Cms::config()->enableCsrfProtection = true;
        Session::start();

        $this->testRenderResult(
            '<input type="hidden" name="_token" value="' . Craft::$app->getRequest()->getCsrfToken() . '">',
            '{{ csrfInput() }}'
        );

        // Custom name - just to be sure.
        Craft::$app->getRequest()->csrfParam = 'HACKER_POOF';
        $this->testRenderResult(
            '<input type="hidden" name="HACKER_POOF" value="' . Craft::$app->getRequest()->getCsrfToken() . '">',
            '{{ csrfInput() }}'
        );
    }

    /**
     * @throws LoaderError
     * @throws SyntaxError
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function test_redirect_input_function(): void
    {
        $this->testRenderResult(
            '<input type="hidden" name="redirect" value="' . Craft::$app->getSecurity()->hashData('A URL') . '">',
            '{{ redirectInput("A URL") }}'
        );

        $this->testRenderResult(
            '<input type="hidden" name="redirect" value="' . Craft::$app->getSecurity()->hashData('A URL WITH CHARS !@#$%^*()😋') . '">',
            '{{ redirectInput("A URL WITH CHARS !@#$%^*()😋") }}'
        );
    }

    /**
     * @throws LoaderError
     * @throws SyntaxError
     */
    public function test_action_input_function(): void
    {
        $this->testRenderResult(
            '<input type="hidden" name="action" value="A URL">',
            '{{ actionInput("A URL") }}'
        );

        $this->testRenderResult(
            '<input type="hidden" name="action" value="A URL WITH CHARS !@#$%^&amp;*()😋">',
            '{{ actionInput("A URL WITH CHARS !@#$%^&*()😋") }}'
        );
    }

    /**
     * @throws LoaderError
     * @throws SyntaxError
     */
    public function test_render_object_template_function(): void
    {
        // This is some next level inception stuff IMO.....
        $this->testRenderResult(
            'John',
            '{{ renderObjectTemplate("{{ object.firstName}}", {firstName: "John"}) }}'
        );
    }

    /**
     * @throws LoaderError
     * @throws SyntaxError
     */
    public function test_getenv_function(): void
    {
        $this->testRenderResult(
            'Craft CMS | info@craftcms.com',
            '{{ getenv("FROM_EMAIL_NAME") }} | {{ getenv("FROM_EMAIL_ADDRESS") }}'
        );
    }

    /**
     * @throws LoaderError
     * @throws SyntaxError
     */
    public function test_parse_env_function(): void
    {
        $this->testRenderResult(
            'Craft CMS',
            '{{ parseEnv("$FROM_EMAIL_NAME") }}'
        );

        $this->testRenderResult(
            'FROM_EMAIL_NAME',
            '{{ parseEnv("FROM_EMAIL_NAME") }}'
        );
    }

    /**
     * @dataProvider collectFunctionDataProvider
     */
    public function test_collect_function(string $expectedClass, array $items): void
    {
        $this->testRenderResult(
            $expectedClass,
            '{{ className(collect(items)) }}',
            ['items' => $items],
        );
    }

    public static function collectFunctionDataProvider(): array
    {
        $entry = new Entry();

        return [
            [Collection::class, []],
            [Collection::class, ['foo']],
            [Collection::class, [$entry, 'foo']],
            [ElementCollection::class, [$entry]],
        ];
    }

    /**
     * @throws LoaderError
     * @throws SyntaxError
     */
    protected function testRenderResult(
        string $expectedString,
        string $renderString,
        array $variables = [],
        string $templateMode = View::TEMPLATE_MODE_SITE,
    ) {
        $result = $this->view->renderString($renderString, $variables, $templateMode);
        self::assertSame(
            $expectedString,
            $result
        );
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
