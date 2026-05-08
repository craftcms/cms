<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\web;

use Craft;
use craft\test\Craft as CraftTest;
use craft\test\mockclasses\arrayable\ExampleArrayable;
use craft\test\mockclasses\models\ExampleModel;
use craft\test\TestCase;
use craft\web\View;
use CraftCms\Aliases\Aliases;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Twig\TemplateResolver;
use CraftCms\Cms\View\Events\SiteTemplateRootsResolving;
use CraftCms\Cms\View\HtmlStack;
use CraftCms\Cms\View\TemplateMode;
use crafttests\fixtures\SitesFixture;
use Illuminate\Support\Facades\Event as LaravelEvent;
use Illuminate\Support\Once;
use ReflectionException;
use Throwable;
use Twig\Error\LoaderError;
use Twig\Error\SyntaxError;
use UnitTester;
use ValueError;
use yii\base\Event;
use yii\base\Exception;

/**
 * Unit tests for the View class
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2
 */
class ViewTest extends TestCase
{
    /**
     * @var UnitTester
     */
    protected UnitTester $tester;

    /**
     * @var View
     */
    protected View $view;

    /**
     * @return array
     */
    public function _fixtures(): array
    {
        return [
            'sites' => [
                'class' => SitesFixture::class,
            ],
        ];
    }

    /**
     * @dataProvider normalizeObjectTemplateDataProvider
     * @param string $expected
     * @param string $template
     */
    public function testNormalizeObjectTemplate(string $expected, string $template): void
    {
        self::assertSame($expected, $this->view->normalizeObjectTemplate($template));
    }

    /**
     *
     */
    public function testDoesTemplateExistWithCustomSite(): void
    {
        // Ensure that the current site is the one with the testSite3 handle
        Sites::setCurrentSite(Sites::getSiteByHandle('testSite3'));

        self::assertSame(
            Aliases::get('@craftunittemplates/testSite3/craft.twig'),
            CraftTest::normalizePathSeparators($this->view->resolveTemplate('craft'))
        );
    }

    /**
     * @dataProvider resolveTemplateDataProvider
     * @param string|false $expected
     * @param string $name
     * @param string|null $templateMode
     * @throws Exception
     */
    public function testResolveTemplate(string|false $expected, string $name, ?string $templateMode = null): void
    {
        Sites::setCurrentSite(Sites::getSiteByHandle('default'));

        if ($templateMode !== null) {
            $this->view->setTemplateMode($templateMode);
        }

        if ($expected !== false) {
            $expected = CraftTest::normalizePathSeparators(Aliases::get($expected));
        }

        self::assertSame($expected, CraftTest::normalizePathSeparators($this->view->resolveTemplate($name)));
    }

    /**
     * @dataProvider privateResolveTemplateDataProvider
     * @param string|null $expected
     * @param string $basePath
     * @param string $name
     * @param string[]|null $defaultTemplateExtensions
     * @param string[]|null $indexTemplateFilenames
     * @throws ReflectionException
     */
    public function testPrivateResolveTemplate(
        ?string $expected,
        string $basePath,
        string $name,
        ?array $defaultTemplateExtensions = null,
        ?array $indexTemplateFilenames = null,
    ) {
        $originalExtensions = Cms::config()->defaultTemplateExtensions;
        $originalFilenames = Cms::config()->indexTemplateFilenames;

        try {
            // If the data wants to set something custom? Set it on the config.
            if ($defaultTemplateExtensions !== null) {
                Cms::config()->defaultTemplateExtensions = $defaultTemplateExtensions;
            }

            // Same with index names
            if ($indexTemplateFilenames !== null) {
                Cms::config()->indexTemplateFilenames = $indexTemplateFilenames;
            }

            // Lets test stuff.
            if ($expected !== null) {
                $expected = CraftTest::normalizePathSeparators(Aliases::get($expected));
            }

            self::assertSame($expected, $this->_resolveTemplate(Aliases::get($basePath), $name));
        } finally {
            Cms::config()->defaultTemplateExtensions = $originalExtensions;
            Cms::config()->indexTemplateFilenames = $originalFilenames;
        }
    }

    /**
     * @throws LoaderError
     * @throws SyntaxError
     */
    public function testRenderString(): void
    {
        $result = $this->view->renderString('{{ arg1 }}-{{ arg2 }}', ['arg1' => 'Craft', 'arg2' => 'CMS']);
        self::assertSame('Craft-CMS', $result);
    }

    /**
     * @dataProvider renderObjectTemplateDataProvider
     * @param string $expected
     * @param string $template
     * @param mixed $object
     * @param array $variables
     * @throws Exception
     * @throws Throwable
     */
    public function testRenderObjectTemplate(string $expected, string $template, mixed $object, array $variables = []): void
    {
        self::assertSame($expected, $this->view->renderObjectTemplate($template, $object, $variables));
    }

    /**
     * @throws Exception
     * @throws ReflectionException
     */
    public function testSetSiteTemplateMode(): void
    {
        $this->view->setTemplateMode(TemplateMode::Site->value);
        self::assertSame(
            Aliases::get('@crafttestsfolder/templates'),
            CraftTest::normalizePathSeparators($this->view->templatesPath)
        );
        self::assertSame(
            ['twig', 'html', 'blade.php'],
            TemplateMode::get()->defaultTemplateExtensions()
        );

        self::assertSame(
            ['index'],
            TemplateMode::get()->indexTemplateFilenames()
        );
    }

    /**
     * @throws Exception
     * @throws ReflectionException
     */
    public function testSetCpTemplateMode(): void
    {
        $this->view->setTemplateMode(TemplateMode::Cp->value);
        self::assertSame(
            Craft::$app->getPath()->getCpTemplatesPath(),
            $this->view->templatesPath
        );

        self::assertSame(
            ['twig', 'html'],
            TemplateMode::get()->defaultTemplateExtensions()
        );

        self::assertSame(
            ['index'],
            TemplateMode::get()->indexTemplateFilenames()
        );
    }

    /**
     *
     */
    public function testTemplateModeException(): void
    {
        $this->tester->expectThrowable(ValueError::class, function() {
            $this->view->setTemplateMode('i dont exist');
        });
    }

    /**
     *
     */
    public function testRegisterTranslationsIsNoop(): void
    {
        // registerTranslations() is now a no-op — all translations are loaded
        // in bulk via window.Craft.translations. Just verify it doesn't throw.
        $this->view->registerTranslations('app', ['Save', 'Cancel']);
    }

    /**
     *
     */
    public function testHookInvocation(): void
    {
        $this->view->hook('demoHook', fn() => '22');
        $this->view->hook('demoHook', fn($val) => $val[0]);

        $var = ['333'];
        self::assertSame('22333', $this->view->invokeHook('demoHook', $var));
        self::assertSame('', $this->view->invokeHook('hook-that-dont-exists', $var));
    }

    /**
     * @dataProvider namespaceInputsDataProvider
     * @param string $expected
     * @param string $html
     * @param string|null $namespace
     * @param bool $otherAttributes
     */
    public function testNamespaceInputs(string $expected, string $html, ?string $namespace = null, bool $otherAttributes = true): void
    {
        self::assertSame($expected, $this->view->namespaceInputs($html, $namespace, $otherAttributes));
    }

    /**
     * @dataProvider namespaceInputNameDataProvider
     * @param string $expected
     * @param string $string
     * @param string|null $namespace
     */
    public function testNamespaceInputName(string $expected, string $string, ?string $namespace = null): void
    {
        self::assertSame($expected, $this->view->namespaceInputName($string, $namespace));
    }

    /**
     * @dataProvider namespaceInputIdDataProvider
     * @param string $expected
     * @param string $string
     * @param string|null $namespace
     */
    public function testNamespaceInputId(string $expected, string $string, ?string $namespace = null): void
    {
        self::assertSame($expected, $this->view->namespaceInputId($string, $namespace));
    }

    /**
     * @dataProvider getTemplateRootsDataProvider
     * @param array $expected
     * @param string $which
     * @param array $roots
     * @throws ReflectionException
     */
    public function testGetTemplateRoots(array $expected, string $which, array $roots): void
    {
        Once::flush();

        LaravelEvent::listen(SiteTemplateRootsResolving::class, function(SiteTemplateRootsResolving $event) use ($roots) {
            $event->roots = $roots;
        });

        self::assertSame($expected, TemplateMode::Site->templateRoots());
    }

    /**
     * Testing these events is quite important as they are quite integral to this function working.
     */
    public function testGetTemplateRootsEvents(): void
    {
        Once::flush();

        $cpEventTriggered = false;
        Event::on(View::class, View::EVENT_REGISTER_CP_TEMPLATE_ROOTS, function() use (&$cpEventTriggered) {
            $cpEventTriggered = true;
        });

        TemplateMode::Cp->templateRoots();
        self::assertTrue($cpEventTriggered, 'Asserting that the CP template roots Yii event is triggered.');

        Once::flush();

        $siteEventTriggered = false;
        Event::on(View::class, View::EVENT_REGISTER_SITE_TEMPLATE_ROOTS, function() use (&$siteEventTriggered) {
            $siteEventTriggered = true;
        });

        TemplateMode::Site->templateRoots();
        self::assertTrue($siteEventTriggered, 'Asserting that the site template roots Yii event is triggered.');
    }

    /**
     * @return void
     */
    public function testJsBuffer(): void
    {
        $view = Craft::$app->getView();

        self::assertFalse($view->clearJsBuffer());

        $view->startJsBuffer();
        $view->registerJs('var foo = true;', View::POS_END);
        $view->registerJs('var bar = true', View::POS_BEGIN);
        self::assertSame("<script type=\"text/javascript\">var bar = true;\nvar foo = true;\n</script>", $view->clearJsBuffer());

        $view->startJsBuffer();
        $view->registerJs('var foo = true;', View::POS_END);
        $view->registerJs('var bar = true', View::POS_BEGIN);
        self::assertSame("var bar = true;\nvar foo = true;\n", $view->clearJsBuffer(false));

        $view->startJsBuffer();
        $view->registerJs('var foo = true;', View::POS_END);
        $view->registerJs('var bar = true', View::POS_BEGIN);
        self::assertSame([
            View::POS_BEGIN => "<script type=\"text/javascript\">var bar = true;</script>",
            View::POS_END => "<script type=\"text/javascript\">var foo = true;</script>",
        ], $view->clearJsBuffer(true, false));

        $view->startJsBuffer();
        $view->registerJs('var foo = true;', View::POS_END, 'foo');
        $view->registerJs('var bar = true', View::POS_BEGIN, 'bar');
        self::assertSame([
            View::POS_BEGIN => [
                'bar' => 'var bar = true;',
            ],
            View::POS_END => [
                'foo' => 'var foo = true;',
            ],
        ], $view->clearJsBuffer(false, false));
    }

    /**
     * @return void
     */
    public function testScriptBuffer(): void
    {
        $view = Craft::$app->getView();

        self::assertFalse($view->clearScriptBuffer());

        $view->startScriptBuffer();
        $view->registerScript('let foo = true', View::POS_END, ['type' => 'module'], 'foo');
        self::assertSame([
            View::POS_END => [
                'foo' => '<script type="module">let foo = true</script>',
            ],
        ], $view->clearScriptBuffer());
    }

    /**
     * @return void
     */
    public function testCssBuffer(): void
    {
        $view = Craft::$app->getView();

        self::assertFalse($view->clearCssBuffer());

        $view->startCssBuffer();
        $view->registerCss('#foo { color: red; }', ['type' => 'text/css'], 'foo');
        self::assertSame([
            'foo' => '<style type="text/css">#foo { color: red; }</style>',
        ], $view->clearCssBuffer());
    }

    /**
     * @return void
     */
    public function testEventTags(): void
    {
        // https://github.com/craftcms/cms/issues/7779
        $expected = <<<TWIG
<html>
<head>
</head>
<body
  x-data="testing"
  x-init=" () => { data.match(/<(.*?)>/) ? alert('wat') }"
>Hello World
</body>
</html>

TWIG;

        $view = Craft::$app->getView();
        Craft::$app->set('view', $this->view);
        self::assertSame($expected, $this->view->renderPageTemplate('event-tags'));
        Craft::$app->set('view', $view);
    }

    public function testRenderPageTemplateTriggersBeginAndEndPageEvents(): void
    {
        $beginTriggered = false;
        $endTriggered = false;

        $beginHandler = function() use (&$beginTriggered) {
            $beginTriggered = true;
        };
        $endHandler = function() use (&$endTriggered) {
            $endTriggered = true;
        };

        Event::on(View::class, View::EVENT_BEGIN_PAGE, $beginHandler);
        Event::on(View::class, View::EVENT_END_PAGE, $endHandler);

        try {
            $this->view->renderPageTemplate('novar.twig');
        } finally {
            Event::off(View::class, View::EVENT_BEGIN_PAGE, $beginHandler);
            Event::off(View::class, View::EVENT_END_PAGE, $endHandler);
        }

        self::assertTrue($beginTriggered);
        self::assertTrue($endTriggered);
    }

    public function testRenderPageTemplateBeforeAndAfterEvents(): void
    {
        $beforeHandler = function($event) {
            $event->template = 'withvar.twig';
            $event->variables = ['name' => 'Template Event'];
        };
        $afterHandler = function($event) {
            $event->output .= ' [after]';
        };

        Event::on(View::class, View::EVENT_BEFORE_RENDER_PAGE_TEMPLATE, $beforeHandler);
        Event::on(View::class, View::EVENT_AFTER_RENDER_PAGE_TEMPLATE, $afterHandler);

        try {
            $output = $this->view->renderPageTemplate('novar.twig');
        } finally {
            Event::off(View::class, View::EVENT_BEFORE_RENDER_PAGE_TEMPLATE, $beforeHandler);
            Event::off(View::class, View::EVENT_AFTER_RENDER_PAGE_TEMPLATE, $afterHandler);
        }

        self::assertSame('Hello iam Template Event [after]', $output);
    }

    /**
     * @return array
     */
    public static function normalizeObjectTemplateDataProvider(): array
    {
        return [
            ['{{ object.titleWithHyphens|replace({\'-\': \'!\'}) }}', '{{ object.titleWithHyphens|replace({\'-\': \'!\'}) }}'],
            ['{{ (_variables.foo ?? object.foo)|raw }}', '{foo}'],
            ['{{ (_variables.foo ?? object.foo).bar|raw }}', '{foo.bar}'],
            ['{foo : \'bar\'}', '{foo : \'bar\'}'],
            ['{{foo}}', '{{foo}}'],
            ['{% foo %}', '{% foo %}'],
            ['{{ (_variables.foo ?? object.foo).fn({bar: baz})|raw }}', '{foo.fn({bar: baz})}'],
            ['{{ (_variables.foo ?? object.foo).fn({bar: {baz: 1}})|raw }}', '{foo.fn({bar: {baz: 1}})}'],
            ['{{ (_variables.foo ?? object.foo).fn(\'bar:baz\')|raw }}', '{foo.fn(\'bar:baz\')}'],
            ['{{ (_variables.foo ?? object.foo).fn({\'bar\': baz})|raw }}', '{foo.fn({\'bar\': baz})}'],
            ['{% verbatim %}`{foo}`{% endverbatim %}', '`{foo}`'],
            ["{% verbatim %}`{foo}\n{bar}`{% endverbatim %}", "`{foo}\n{bar}`"],
            ["{% verbatim %}```\n{% exit %}\n```{% endverbatim %}", "```\n{% exit %}\n```"],
            ["{% verbatim %}````\n{% exit %}\n````{% endverbatim %}", "````\n{% exit %}\n````"],
            ["{% verbatim %}\n{foo}\n{% endverbatim %}", "{% verbatim %}\n{foo}\n{% endverbatim %}"],
            ["{%- verbatim -%}\n{foo}\n{%- endverbatim -%}", "{%- verbatim -%}\n{foo}\n{%- endverbatim -%}"],
            ['{{ clone(productCategory).level(1).one().slug|raw }}', '{clone(productCategory).level(1).one().slug}'],
            ['{{ #{foo} }}', '{{ #{foo} }}'],
            ['{% set string = "test #{foo} 5" %}{{string}}', '{% set string = "test #{foo} 5" %}{{string}}'],
        ];
    }

    /**
     * @return array
     */
    public static function resolveTemplateDataProvider(): array
    {
        return [
            ['@craftunittemplates/index.html', ''],
            ['@craftunittemplates/template.twig', 'template'],
            [false, 'doesntExist'],
            [false, '@craftunittemplates/index.html'],
            ['@craftunittemplates/testSite3/index.twig', 'testSite3/index.twig'],
            ['@craftunittemplates/testSite3/index.twig', 'testSite3'],
            ['@craftunittemplates/testSite3/index.twig', 'testSite3/'],

            // Cp Paths
            ['@craftcms/resources/templates/entries/index.twig', 'entries', TemplateMode::Cp->value],
        ];
    }

    /**
     * @return array
     */
    public static function privateResolveTemplateDataProvider(): array
    {
        return [
            ['@craftunittemplates/template.twig', '@craftunittemplates', 'template'],
            ['@craftunittemplates/index.html', '@craftunittemplates', 'index'],
            ['@craftunittemplates/doubleindex/index.twig', '@craftunittemplates/doubleindex', 'index'],

            // Index is found by default
            ['@craftunittemplates/index.html', '@craftunittemplates', ''],

            // Assert that registering custom extensions works.
            ['@craftunittemplates/dotxml.xml', '@craftunittemplates', 'dotxml', ['xml']],
            [null, '@craftunittemplates', 'dotxml'],
            ['@craftunittemplates/dotxml.xml', '@craftunittemplates', 'dotxml.xml'],

            // Allow change in index names
            ['@craftunittemplates/template.twig', '@craftunittemplates', '', null, ['template']],
        ];
    }

    /**
     * @return array
     */
    public static function renderObjectTemplateDataProvider(): array
    {
        $model = new ExampleModel();
        $model->exampleParam = 'Example Param';

        $arrayable = new ExampleArrayable();
        $arrayable->exampleArrayableParam = 'Example param';
        $arrayable->extraField = 'ExtraField';

        return [
            // No tags. Then it returns the template
            ['[[ exampleParam ]]', '[[ exampleParam ]]', $model, ['vars' => 'vars']],

            // Base arrayable test
            ['Example paramExample param', '{ exampleArrayableParam }{ object.exampleArrayableParam }', $arrayable],
            ['ExtraFieldExtraField', '{ extraField }{ object.extraField }', $arrayable],

            // Base model test
            ['Example ParamExample Param', '{{ exampleParam }}{{ object.exampleParam }}', $model],
            ['Example ParamExample Param', '{ exampleParam }{ object.exampleParam }', $model],

            // Test that model params dont override variable params.
            ['IM DIFFERENTExample Param', '{ exampleParam }{ object.exampleParam }', $model, ['exampleParam' => 'IM DIFFERENT']],

            // Test basic arrays
            ['foo=bar', 'foo={foo}', ['foo' => 'bar']],

            // Make sure resulting templates are trimmed
            ['foo', ' foo ', $model],
            ['Example Param', ' {exampleParam}', $model],
        ];
    }

    /**
     * @return array
     */
    public static function namespaceInputsDataProvider(): array
    {
        return [
            ['', ''],
            ['<input type="text" name="test">', '<input type="text" name="test">'],
            ['<input type="text" name="namespace[test]">', '<input type="text" name="test">', 'namespace'],
            ['<input type="text" for="test3" id="namespace-test2"  name="namespace[test]">', '<input type="text" for="test3" id="test2"  name="test">', 'namespace'],
            ['<input type="text" value="im the input" name="namespace[test]">', '<input type="text" value="im the input" name="test">', 'namespace'],
            ['<textarea id="namespace-test">Im the content</textarea>', '<textarea id="test">Im the content</textarea>', 'namespace'],
            ['<not-html id="namespace-test"></not-html>', '<not-html id="test"></not-html>', 'namespace'],

            ['<input im-not-html-tho="test2">', '<input im-not-html-tho="test2">', 'namespace'],
            ['<input data-target="test2">', '<input data-target="test2">', 'namespace', false],

            // Other attributes
            ['<input data-target="test2">', '<input data-target="test2">', 'namespace', true],
            ['<input aria-describedby="test2">', '<input aria-describedby="test2">', 'namespace', true],
            ['<input aria-not-a-tag="test2">', '<input aria-not-a-tag="test2">', 'namespace', true],
            ['<input data-reverse-target="test2">', '<input data-reverse-target="test2">', 'namespace', true],
            ['<input data-target-prefix="namespace-test2">', '<input data-target-prefix="test2">', 'namespace', true],
            ['<input aria-labelledby="test2">', '<input aria-labelledby="test2">', 'namespace', true],
            ['<input data-random="test2">', '<input data-random="test2">', 'namespace', true],
        ];
    }

    /**
     * @return array
     */
    public static function namespaceInputNameDataProvider(): array
    {
        return [
            ['', ''],
            ['<input type="text" name="test">', '<input type="text" name="test">'],
            ['namespace[<input type=]"namespace[text]"namespace[ name=]"namespace[test]"namespace[>]', '<input type="text" name="test">', 'namespace'],
            ['!@#$%^&*()_+{}:"<>?[<input type=]"!@#$%^&*()_+{}:"<>?[text]"!@#$%^&*()_+{}:"<>?[ name=]"!@#$%^&*()_+{}:"<>?[test]"!@#$%^&*()_+{}:"<>?[>]', '<input type="text" name="test">', '!@#$%^&*()_+{}:"<>?'],
            ['namespace[<input type=]"namespace[text]"namespace[ for=]"namespace[test3]"namespace[ id=]"namespace[test2]"namespace[  name=]"namespace[test]"namespace[>]', '<input type="text" for="test3" id="test2"  name="test">', 'namespace'],
            ['namespace[<input im-not-html-tho=]"namespace[test2]"namespace[>]', '<input im-not-html-tho="test2">', 'namespace'],
            ['namespace[<input type=]"namespace[text]"namespace[ value=]"namespace[im the input]"namespace[ name=]"namespace[test]"namespace[>]', '<input type="text" value="im the input" name="test">', 'namespace'],
            ['namespace[<textarea id=]"namespace[test]"namespace[>Im the content</textarea>]', '<textarea id="test">Im the content</textarea>', 'namespace'],
            ['namespace[<not-html id=]"namespace[test]"namespace[></not-html>]', '<not-html id="test"></not-html>', 'namespace'],
        ];
    }

    /**
     * @return array
     */
    public static function namespaceInputIdDataProvider(): array
    {
        return [
            ['', ''],
            ['foo-bar', 'bar', 'foo'],
            ['foo-bar-baz', 'bar[baz]', 'foo'],
            ['foo-bar-baz', 'baz', 'foo[bar]'],
        ];
    }

    /**
     * @return array
     */
    public static function getTemplateRootsDataProvider(): array
    {
        return [
            [['random-roots' => [null]], 'random-roots', ['random-roots' => [null]]],
            [['random-roots' => ['/linux/box/craft/templates']], 'random-roots', ['random-roots' => '/linux/box/craft/templates']],
            [['random-roots' => ['windows/box/craft/templates', '/linux/box/craft/templates']], 'random-roots', ['random-roots' => ['windows/box/craft/templates', '/linux/box/craft/templates']]],
        ];
    }

    /**
     * @inheritdoc
     */
    protected function _before(): void
    {
        parent::_before();

        // Clear the asset registry to prevent state leaking between tests
        app(HtmlStack::class)->clear();

        $this->view = Craft::createObject(View::class);

        // By default we want to be in site mode.
        $this->view->setTemplateMode(TemplateMode::Site->value);
    }

    /**
    /**
     * @param string $which
     * @return array
     * @throws ReflectionException
     */
    private function _getTemplateRoots(string $which): array
    {
        return $this->invokeMethod($this->view, '_getTemplateRoots', [$which]);
    }

    /**
     * @param string $basePath
     * @param string $name
     * @param bool $publicOnly
     * @return string|null
     * @throws ReflectionException
     */
    private function _resolveTemplate(string $basePath, string $name, bool $publicOnly = false): ?string
    {
        $path = $this->invokeMethod(new TemplateResolver(), 'resolveFromPath', [$basePath, $name, $publicOnly]);
        if ($path !== null) {
            $path = CraftTest::normalizePathSeparators($path);
        }
        return $path;
    }
}
