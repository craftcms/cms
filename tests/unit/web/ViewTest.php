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
use craft\test\mockclasses\arrayable\ExampleArrayble;
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
    // Public Properties
    // =========================================================================

    /**
     * @var UnitTester
     */
    protected $tester;

    /**
     * @var View
     */
    protected $view;

    // Public Methods
    // =========================================================================

    /**
     * @return array
     */
    public function _fixtures(): array
    {
        return [
            'sites' => [
                'class' => SitesFixture::class
            ]
        ];
    }

    // Tests
    // =========================================================================

    /**
     * @dataProvider normalizeObjectTemplateDataProvider
     *
     * @param $result
     * @param $input
     */
    public function testNormalizeObjectTemplate($result, $input)
    {
        $this->assertSame($result, $this->view->normalizeObjectTemplate($input));
    }

    /**
     *
     */
    public function testDoesTemplateExistWithCustomSite()
    {
        // Ensure that the current site is the one with the testSite3 handle
        Craft::$app->getSites()->setCurrentSite(Craft::$app->getSites()->getSiteByHandle('testSite3'));

        $this->assertSame(
            Craft::getAlias('@craftunittemplates/testSite3/craft.twig'),
            CraftTest::normalizePathSeparators($this->view->resolveTemplate('craft'))
        );
    }

    /**
     * @dataProvider doesTemplateExistDataProvider
     *
     * @param $result
     * @param $templatePath
     * @param null $templateMode
     * @throws Exception
     */
    public function testDoesTemplateExistInSite($result, $templatePath, $templateMode = null)
    {
        if ($templateMode !== null) {
            $this->view->setTemplateMode($templateMode);
        }

        $doesIt = CraftTest::normalizePathSeparators($this->view->resolveTemplate($templatePath));

        if ($result === false) {
            $this->assertFalse($doesIt);
        } else {
            $this->assertSame(CraftTest::normalizePathSeparators(Craft::getAlias($result)), $doesIt);
        }
    }

    /**
     * @dataProvider       privateResolveTemplateDataProvider
     *
     * @param $result
     * @param $basePath
     * @param $name
     * @param null $templateExtensions
     * @param null $viewTemplateNameExtensions
     * @throws ReflectionException
     * @see                testDoesTemplateExistsInSite
     */
    public function testPrivateResolveTemplate($result, $basePath, $name, $templateExtensions = null, $viewTemplateNameExtensions = null)
    {
        // If the data wants to set something custom? Set it as a prop.
        if ($templateExtensions !== null) {
            $this->setInaccessibleProperty($this->view, '_defaultTemplateExtensions', $templateExtensions);
        }

        // Same with index names
        if ($viewTemplateNameExtensions !== null) {
            $this->setInaccessibleProperty($this->view, '_indexTemplateFilenames', $viewTemplateNameExtensions);
        }

        // Lets test stuff.
        $resolved = $this->_resolveTemplate(Craft::getAlias($basePath), $name);
        $this->assertSame(CraftTest::normalizePathSeparators(Craft::getAlias($result)), $resolved);
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
    public function testRenderTemplate()
    {
        // Assert that the _renderingTemplate prop goes in and comes out as null.
        $this->assertNull($this->getInaccessibleProperty($this->view, '_renderingTemplate'));

        $result = $this->view->renderTemplate('withvar', ['name' => 'Giel Tettelaar']);

        $this->assertSame($result, 'Hello iam Giel Tettelaar');
        $this->assertNull($this->getInaccessibleProperty($this->view, '_renderingTemplate'));

        // Test that templates can work without variables.
        $result = $this->view->renderTemplate('novar');

        $this->assertSame($result, 'I have no vars');
    }

    /**
     * @throws Exception
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function testRenderMacro()
    {
        $this->view->setTemplateMode(View::TEMPLATE_MODE_SITE);
        $result = $this->view->renderTemplateMacro('macros', 'testMacro1', ['arg1' => 'Craft', 'arg2' => 'CMS']);
        $this->assertSame('Craft-CMS', $result);
    }

    /**
     * @throws LoaderError
     * @throws SyntaxError
     */
    public function testRenderString()
    {
        $result = $this->view->renderString('{{ arg1 }}-{{ arg2 }}', ['arg1' => 'Craft', 'arg2' => 'CMS']);
        $this->assertSame('Craft-CMS', $result);
    }

    /**
     * @dataProvider renderObjectTemplateDataProvider
     *
     * @param $result
     * @param $template
     * @param $object
     * @param array $variables
     * @throws Exception
     * @throws Throwable
     */
    public function testRenderObjectTemplate($result, $template, $object, array $variables = [])
    {
        $res = $this->view->renderObjectTemplate($template, $object, $variables);
        $this->assertSame($result, $res);
    }

    /**
     * @throws Exception
     * @throws ReflectionException
     */
    public function testSetSiteTemplateMode()
    {
        $this->view->setTemplateMode(View::TEMPLATE_MODE_SITE);
        $this->assertSame(
            Craft::getAlias('@crafttestsfolder/templates'),
            CraftTest::normalizePathSeparators($this->view->templatesPath)
        );
        $this->assertSame(
            ['html', 'twig'],
            $this->getInaccessibleProperty($this->view, '_defaultTemplateExtensions')
        );

        $this->assertSame(
            ['index'],
            $this->getInaccessibleProperty($this->view, '_indexTemplateFilenames')
        );
    }

    /**
     * @throws Exception
     * @throws ReflectionException
     */
    public function testSetCpTemplateMode()
    {
        $this->view->setTemplateMode(View::TEMPLATE_MODE_CP);
        $this->assertSame(
            Craft::$app->getPath()->getCpTemplatesPath(),
            $this->view->templatesPath
        );

        $this->assertSame(
            ['html', 'twig'],
            $this->getInaccessibleProperty($this->view, '_defaultTemplateExtensions')
        );

        $this->assertSame(
            ['index'],
            $this->getInaccessibleProperty($this->view, '_indexTemplateFilenames')
        );
    }

    /**
     *
     */
    public function testTemplateModeException()
    {
        $this->tester->expectThrowable(Exception::class, function() {
            $this->view->setTemplateMode('i dont exist');
        });
    }

    /**
     *
     */
    public function testRegisterTranslations()
    {
        Craft::$app->language = 'nl';

        // Basic test that register translations gets rendered
        $js = $this->_generateTranslationJs('app', ['1 month' => '1 maand', '1 minute' => '1 minuut']);
        $this->_assertRegisterJsInputValues($js, View::POS_BEGIN);
        $this->view->registerTranslations('app', ['1 month', '1 minute']);

        // Non existing translations get ignored
        $js = $this->_generateTranslationJs('app', ['1 month' => '1 maand']);
        $this->_assertRegisterJsInputValues($js, View::POS_BEGIN);
        $this->view->registerTranslations('app', ['1 month', 'not an existing translation23131321313']);
    }

    /**
     *
     */
    public function testHookInvocation()
    {
        $this->setInaccessibleProperty($this->view, '_hooks', [
            'demoHook' => [
                function() {
                    return '22';
                },
                function($val) {
                    return $val[0];
                }
            ]
        ]);

        $var = ['333'];
        $this->assertSame('22333', $this->view->invokeHook('demoHook', $var));
        $this->assertSame('', $this->view->invokeHook('hook-that-dont-exists', $var));
    }

    /**
     * @dataProvider namespaceInputsDataProvider
     *
     * @param $result
     * @param $html
     * @param null $namespace
     * @param bool $otherAttributes
     */
    public function testNamespaceInputs($result, $html, $namespace = null, $otherAttributes = true)
    {
        $namespaced = $this->view->namespaceInputs($html, $namespace, $otherAttributes);
        $this->assertSame($result, $namespaced);
    }

    /**
     * @dataProvider namespaceInputNameDataProvider
     *
     * @param $result
     * @param $string
     * @param $namespace
     */
    public function testNamespaceInputName($result, $string, $namespace = null)
    {
        $namespaced = $this->view->namespaceInputName($string, $namespace);
        $this->assertSame($result, $namespaced);
    }

    /**
     * @dataProvider namespaceInputIdDataProvider
     *
     * @param $result
     * @param $string
     * @param $namespace
     */
    public function testNamespaceInputId($result, $string, $namespace = null)
    {
        $namespaced = $this->view->namespaceInputId($string, $namespace);
        $this->assertSame($result, $namespaced);
    }

    /**
     * @dataProvider getTemplateRootsDataProvider
     *
     * @param $result
     * @param $which
     * @param $rootsToBeAdded
     * @throws ReflectionException
     */
    public function testGetTemplateRoots($result, $which, $rootsToBeAdded)
    {
        Event::on(View::class, View::EVENT_REGISTER_SITE_TEMPLATE_ROOTS, function(RegisterTemplateRootsEvent $event) use ($rootsToBeAdded) {
            $event->roots = $rootsToBeAdded;
        });

        $roots = $this->_getTemplateRoots($which);
        $this->assertSame($result, $roots);
    }

    /**
     * Testing these events is quite important as they are quite integral to this function working.
     */
    public function testGetTemplateRootsEvents()
    {
        $this->tester->expectEvent(View::class, View::EVENT_REGISTER_CP_TEMPLATE_ROOTS, function() {
            $this->_getTemplateRoots('cp');
        });
        $this->tester->expectEvent(View::class, View::EVENT_REGISTER_SITE_TEMPLATE_ROOTS, function() {
            $this->_getTemplateRoots('doesnt-matter-what-this-is');
        });
    }

    /**
     * Basic test to check the Registered js function
     */
    public function testRegisteredJs()
    {
        $property = 'randomprop';
        $name = 'name';
        $resultString = "if (typeof Craft !== 'undefined') {\n";
        $jsName = Json::encode($name);
        $resultString .= "  Craft.{$property}[{$jsName}] = true;\n";
        $resultString .= '}';

        // Set a stub and ensure that _registeredJs is correctly formatting js but dont bother registering it....
        $this->_assertRegisterJsInputValues($resultString, View::POS_HEAD);

        $this->_registeredJs('randomprop', ['name' => 'value']);
    }

    // Data Providers
    // =========================================================================

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
            ['{{ (_variables.foo ?? object.foo).fn({\'bar\': baz})|raw }}', '{foo.fn({\'bar\': baz})}']
        ];
    }

    /**
     * @return array
     */
    public function doesTemplateExistDataProvider(): array
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
            ['@craft/templates/index.html', '', View::TEMPLATE_MODE_CP],
            ['@craft/templates/index.html', 'index', View::TEMPLATE_MODE_CP],
            ['@craft/templates/entries/index.html', 'entries', View::TEMPLATE_MODE_CP],
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
            ['@craftunittemplates/dotxml.xml', '@craftunittemplates', 'dotxml.xml',],

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

        $arrayable = new ExampleArrayble();
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
            ['<input type="text" for="namespace-test3" id="namespace-test2"  name="namespace[test]">', '<input type="text" for="test3" id="test2"  name="test">', 'namespace'],
            ['<input type="text" value="im the input" name="namespace[test]">', '<input type="text" value="im the input" name="test">', 'namespace'],
            ['<textarea id="namespace-test">Im the content</textarea>', '<textarea id="test">Im the content</textarea>', 'namespace'],
            ['<not-html id="namespace-test"></not-html>', '<not-html id="test"></not-html>', 'namespace'],

            ['<input im-not-html-tho="test2">', '<input im-not-html-tho="test2">', 'namespace'],
            ['<input data-target="test2">', '<input data-target="test2">', 'namespace', false],

            // Other attributes
            ['<input data-target="namespace-test2">', '<input data-target="test2">', 'namespace', true],
            ['<input aria-describedby="test2">', '<input aria-describedby="test2">', 'namespace', true],
            ['<input aria-not-a-tag="test2">', '<input aria-not-a-tag="test2">', 'namespace', true],
            ['<input data-reverse-target="namespace-test2">', '<input data-reverse-target="test2">', 'namespace', true],
            ['<input data-target-prefix="namespace-test2">', '<input data-target-prefix="test2">', 'namespace', true],
            ['<input aria-labelledby="namespace-test2">', '<input aria-labelledby="test2">', 'namespace', true],
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
            ['<input type="text" name="test">', '<input type="text" name="test">'],
            ['namespace-<input type="text" name="test">', '<input type="text" name="test">', 'namespace'],
            ['!@#$%^&*()_+{}:"<>?-<input type="text" name="test">', '<input type="text" name="test">', '!@#$%^&*()_+{}:"<>?'],
            ['namespace-<input type="text" for="test3" id="test2"  name="test">', '<input type="text" for="test3" id="test2"  name="test">', 'namespace'],
            ['namespace-<input im-not-html-tho="test2">', '<input im-not-html-tho="test2">', 'namespace'],
            ['namespace-<input type="text" value="im the input" name="test">', '<input type="text" value="im the input" name="test">', 'namespace'],
            ['namespace-<textarea id="test">Im the content</textarea>', '<textarea id="test">Im the content</textarea>', 'namespace'],
            ['namespace-<not-html id="test"></not-html>', '<not-html id="test"></not-html>', 'namespace'],
        ];
    }

    /**
     * @return array
     */
    public function getTemplateRootsDataProvider(): array
    {
        return [
            [['random-roots' => [null]], 'random-roots', ['random-roots' => null]],
            [['random-roots' => ['/linux/box/craft/templates']], 'random-roots', ['random-roots' => '/linux/box/craft/templates']],
            [['random-roots' => [['windows/box/craft/templates', '/linux/box/craft/templates']]], 'random-roots', ['random-roots' => ['windows/box/craft/templates', '/linux/box/craft/templates']]],
        ];
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function _before()
    {
        parent::_before();

        $this->view = Craft::createObject(View::class);

        // By default we want to be in site mode.
        $this->view->setTemplateMode(View::TEMPLATE_MODE_SITE);
    }

    // Private Methods
    // =========================================================================

    /**
     * @param $category
     * @param array $messages
     * @return string
     */
    private function _generateTranslationJs($category, array $messages): string
    {
        $category = Json::encode($category);
        $js = '';
        foreach ($messages as $message => $translation) {
            $translation = Json::encode($translation);
            $message = Json::encode($message);
            $js .= ($js !== '' ? PHP_EOL : '') . "Craft.translations[{$category}][{$message}] = {$translation};";
        }

        return "if (typeof Craft.translations[{$category}] === 'undefined') {" . PHP_EOL . "    Craft.translations[{$category}] = {};" . PHP_EOL . '}' . PHP_EOL . $js;
    }

    /**
     * @param $desiredJs
     * @param $desiredPosition
     * @throws \Exception
     */
    private function _assertRegisterJsInputValues($desiredJs, $desiredPosition)
    {
        $this->view = Stub::construct(
            View::class,
            [],
            [
                'registerJs' => function($inputJs, $inputPosition) use ($desiredJs, $desiredPosition) {
                    $this->assertSame($desiredJs, $inputJs);
                    $this->assertSame($desiredPosition, $inputPosition);
                }
            ]
        );
    }

    /**
     * @param $property
     * @param $names
     * @return mixed
     * @throws ReflectionException
     */
    private function _registeredJs($property, $names)
    {
        return $this->invokeMethod($this->view, '_registeredJs', [$property, $names]);
    }

    /**
     * @param $which
     * @return mixed
     * @throws ReflectionException
     */
    private function _getTemplateRoots($which)
    {
        return $this->invokeMethod($this->view, '_getTemplateRoots', [$which]);
    }

    /**
     * @param $basePath
     * @param $name
     * @return mixed
     * @throws ReflectionException
     */
    private function _resolveTemplate($basePath, $name)
    {
        return CraftTest::normalizePathSeparators($this->invokeMethod($this->view, '_resolveTemplate', [$basePath, $name]));
    }
}
