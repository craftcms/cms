<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\web;

use Codeception\Stub;
use Craft;
use craft\events\RegisterTemplateRootsEvent;
use craft\helpers\Json;
use craft\test\Craft as CraftTest;
use craft\test\mockclasses\arrayable\ExampleArrayable;
use craft\test\mockclasses\models\ExampleModel;
use craft\test\TestCase;
use craft\web\View;
use crafttests\fixtures\SitesFixture;
use ReflectionException;
use Throwable;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use UnitTester;
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
        Craft::$app->getSites()->setCurrentSite(Craft::$app->getSites()->getSiteByHandle('testSite3'));

        self::assertSame(
            Craft::getAlias('@craftunittemplates/testSite3/craft.twig'),
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
        if ($templateMode !== null) {
            $this->view->setTemplateMode($templateMode);
        }

        if ($expected !== false) {
            $expected = CraftTest::normalizePathSeparators(Craft::getAlias($expected));
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
        // If the data wants to set something custom? Set it as a prop.
        if ($defaultTemplateExtensions !== null) {
            $this->setInaccessibleProperty($this->view, '_defaultTemplateExtensions', $defaultTemplateExtensions);
        }

        // Same with index names
        if ($indexTemplateFilenames !== null) {
            $this->setInaccessibleProperty($this->view, '_indexTemplateFilenames', $indexTemplateFilenames);
        }

        // Lets test stuff.
        if ($expected !== null) {
            $expected = CraftTest::normalizePathSeparators(Craft::getAlias($expected));
        }

        self::assertSame($expected, $this->_resolveTemplate(Craft::getAlias($basePath), $name));
    }

    /**
     * Test that Craft::$app->getView()->renderTemplates(); Seems to work correctly with twig. Doesnt impact global props
     * and respects passed in variables.
     *
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws ReflectionException
     */
    public function testRenderTemplate(): void
    {
        // Assert that the _renderingTemplate prop goes in and comes out as null.
        self::assertNull($this->getInaccessibleProperty($this->view, '_renderingTemplate'));

        $result = $this->view->renderTemplate('withvar', ['name' => 'Giel Tettelaar']);

        self::assertSame($result, 'Hello iam Giel Tettelaar');
        self::assertNull($this->getInaccessibleProperty($this->view, '_renderingTemplate'));

        // Test that templates can work without variables.
        $result = $this->view->renderTemplate('novar');

        self::assertSame($result, 'I have no vars');
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
        $this->view->setTemplateMode(View::TEMPLATE_MODE_SITE);
        self::assertSame(
            Craft::getAlias('@crafttestsfolder/templates'),
            CraftTest::normalizePathSeparators($this->view->templatesPath)
        );
        self::assertSame(
            ['html', 'twig'],
            $this->getInaccessibleProperty($this->view, '_defaultTemplateExtensions')
        );

        self::assertSame(
            ['index'],
            $this->getInaccessibleProperty($this->view, '_indexTemplateFilenames')
        );
    }

    /**
     * @throws Exception
     * @throws ReflectionException
     */
    public function testSetCpTemplateMode(): void
    {
        $this->view->setTemplateMode(View::TEMPLATE_MODE_CP);
        self::assertSame(
            Craft::$app->getPath()->getCpTemplatesPath(),
            $this->view->templatesPath
        );

        self::assertSame(
            ['html', 'twig'],
            $this->getInaccessibleProperty($this->view, '_defaultTemplateExtensions')
        );

        self::assertSame(
            ['index'],
            $this->getInaccessibleProperty($this->view, '_indexTemplateFilenames')
        );
    }

    /**
     *
     */
    public function testTemplateModeException(): void
    {
        $this->tester->expectThrowable(Exception::class, function() {
            $this->view->setTemplateMode('i dont exist');
        });
    }

    /**
     *
     */
    public function testRegisterTranslations(): void
    {
        Craft::$app->language = 'nl';

        // Basic test that register translations gets rendered
        $js = $this->_generateTranslationJs('app', ['Save' => 'Bewaren', 'Cancel' => 'Afbreken']);
        $this->_assertRegisterJsInputValues($js, View::POS_BEGIN);
        $this->view->registerTranslations('app', ['Save', 'Cancel']);
    }

    /**
     *
     */
    public function testHookInvocation(): void
    {
        $this->setInaccessibleProperty($this->view, '_hooks', [
            'demoHook' => [
                function() {
                    return '22';
                },
                function($val) {
                    return $val[0];
                },
            ],
        ]);

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
        Event::on(View::class, View::EVENT_REGISTER_SITE_TEMPLATE_ROOTS, function(RegisterTemplateRootsEvent $event) use ($roots) {
            $event->roots = $roots;
        });

        self::assertSame($expected, $this->_getTemplateRoots($which));
    }

    /**
     * Testing these events is quite important as they are quite integral to this function working.
     */
    public function testGetTemplateRootsEvents(): void
    {
        $this->tester->expectEvent(View::class, View::EVENT_REGISTER_CP_TEMPLATE_ROOTS, function() {
            $this->_getTemplateRoots('cp');
        });
        $this->tester->expectEvent(View::class, View::EVENT_REGISTER_SITE_TEMPLATE_ROOTS, function() {
            $this->_getTemplateRoots('doesnt-matter-what-this-is');
        });
    }

    /**
     * @return void
     */
    public function testJsBuffer(): void
    {
        $view = Craft::$app->getView();

        $this->assertFalse($view->clearJsBuffer());

        $view->startJsBuffer();
        $view->registerJs('var foo = true;', View::POS_END);
        $view->registerJs('var bar = true', View::POS_BEGIN);
        $this->assertSame("<script type=\"text/javascript\">var bar = true;\nvar foo = true;\n</script>", $view->clearJsBuffer());

        $view->startJsBuffer();
        $view->registerJs('var foo = true;', View::POS_END);
        $view->registerJs('var bar = true', View::POS_BEGIN);
        $this->assertSame("var bar = true;\nvar foo = true;\n", $view->clearJsBuffer(false));

        $view->startJsBuffer();
        $view->registerJs('var foo = true;', View::POS_END);
        $view->registerJs('var bar = true', View::POS_BEGIN);
        $this->assertSame([
            View::POS_END => "<script type=\"text/javascript\">var foo = true;</script>",
            View::POS_BEGIN => "<script type=\"text/javascript\">var bar = true;</script>",
        ], $view->clearJsBuffer(true, false));

        $view->startJsBuffer();
        $view->registerJs('var foo = true;', View::POS_END, 'foo');
        $view->registerJs('var bar = true', View::POS_BEGIN, 'bar');
        $this->assertSame([
            View::POS_END => [
                'foo' => 'var foo = true;',
            ],
            View::POS_BEGIN => [
                'bar' => 'var bar = true;',
            ],
        ], $view->clearJsBuffer(false, false));
    }

    /**
     * @return void
     */
    public function testScriptBuffer(): void
    {
        $view = Craft::$app->getView();

        $this->assertFalse($view->clearScriptBuffer());

        $view->startScriptBuffer();
        $view->registerScript('let foo = true', View::POS_END, ['type' => 'module'], 'foo');
        $this->assertSame([
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

        $this->assertFalse($view->clearCssBuffer());

        $view->startCssBuffer();
        $view->registerCss('#foo { color: red; }', ['type' => 'text/css'], 'foo');
        $this->assertSame([
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
        $this->assertSame($expected, $this->view->renderPageTemplate('event-tags'));
        Craft::$app->set('view', $view);
    }

    /**
     * @return array
     */
    public function normalizeObjectTemplateDataProvider(): array
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
    public function resolveTemplateDataProvider(): array
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
            ['@craft/templates/index.twig', '', View::TEMPLATE_MODE_CP],
            ['@craft/templates/index.twig', 'index', View::TEMPLATE_MODE_CP],
            ['@craft/templates/entries/index.twig', 'entries', View::TEMPLATE_MODE_CP],
        ];
    }

    /**
     * @return array
     */
    public function privateResolveTemplateDataProvider(): array
    {
        return [
            ['@craftunittemplates/template.twig', '@craftunittemplates', 'template'],
            ['@craftunittemplates/index.html', '@craftunittemplates', 'index'],
            ['@craftunittemplates/doubleindex/index.html', '@craftunittemplates/doubleindex', 'index'],

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
    public function renderObjectTemplateDataProvider(): array
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
        ];
    }

    /**
     * @return array
     */
    public function namespaceInputsDataProvider(): array
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
    public function namespaceInputNameDataProvider(): array
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
    public function namespaceInputIdDataProvider(): array
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
    public function getTemplateRootsDataProvider(): array
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

        $this->view = Craft::createObject(View::class);

        // By default we want to be in site mode.
        $this->view->setTemplateMode(View::TEMPLATE_MODE_SITE);
    }

    /**
     * @param mixed $category
     * @param array $messages
     * @return string
     */
    private function _generateTranslationJs(mixed $category, array $messages): string
    {
        $category = Json::encode($category);
        $js = '';
        foreach ($messages as $message => $translation) {
            $translation = Json::encode($translation);
            $message = Json::encode($message);
            $js .= ($js !== '' ? PHP_EOL : '') . "Craft.translations[$category][$message] = $translation;";
        }

        return "if (typeof Craft.translations[$category] === 'undefined') {" . PHP_EOL . "    Craft.translations[$category] = {};" . PHP_EOL . '}' . PHP_EOL . $js;
    }

    /**
     * @param mixed $desiredJs
     * @param mixed $desiredPosition
     * @throws \Exception
     */
    private function _assertRegisterJsInputValues(mixed $desiredJs, mixed $desiredPosition)
    {
        $this->view = Stub::construct(
            View::class,
            [],
            [
                'registerJs' => function($inputJs, $inputPosition) use ($desiredJs, $desiredPosition) {
                    self::assertSame($desiredJs, $inputJs);
                    self::assertSame($desiredPosition, $inputPosition);
                },
            ]
        );
    }

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
     * @return string|null
     * @throws ReflectionException
     */
    private function _resolveTemplate(string $basePath, string $name): ?string
    {
        $path = $this->invokeMethod($this->view, '_resolveTemplate', [$basePath, $name]);
        if ($path !== null) {
            $path = CraftTest::normalizePathSeparators($path);
        }
        return $path;
    }
}
