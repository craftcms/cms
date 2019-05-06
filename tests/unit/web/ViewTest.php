<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */
namespace craftunit\web;


use Codeception\Stub;
use Craft;
use craft\events\RegisterTemplateRootsEvent;
use craft\helpers\Json;
use craft\test\mockclasses\arrayable\ExampleArrayble;
use craft\test\mockclasses\models\ExampleModel;
use craft\test\TestCase;
use craft\web\View;
use craftunit\fixtures\SitesFixture;
use UnitTester;
use yii\base\Event;
use yii\base\Exception;

/**
 * Unit tests for the View class
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.0
 */
class ViewTest extends TestCase
{
    public function _fixtures()
    {
        return [
            'sites' => [
                'class' => SitesFixture::class
            ]
        ];
    }

    /**
     * @var UnitTester
     */
    protected $tester;

    /**
     * @var View $view
     */
    protected $view;

    public function _before()
    {
        parent::_before();

        $this->view = Craft::createObject(View::class);

        // By default we want to be in site mode.
        $this->view->setTemplateMode(View::TEMPLATE_MODE_SITE);
    }


    /**
     * @param $result
     * @param $input
     * @dataProvider normalizeObjectTemplateData
     */
    public function testNormalizeObjectTemplate($result, $input)
    {
        $this->assertSame($result, $this->view->normalizeObjectTemplate($input));
    }

    public function normalizeObjectTemplateData()
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

    public function testDoesTemplateExistWithCustomSite()
    {
        // Ensure that the current site is the one with the testSite3 handle
        Craft::$app->getSites()->setCurrentSite(Craft::$app->getSites()->getSiteByHandle('testSite3'));

        $this->assertSame(
            Craft::getAlias('@craftunittemplates/testSite3/craft.twig'),
            $this->view->resolveTemplate('craft')
        );
    }

    /**
     * @param $result
     * @param $templatePath
     * @param null $templateMode
     * @throws Exception
     * @dataProvider doesTemplateExistData
     */
    public function testDoesTemplateExistsInSite($result, $templatePath, $templateMode = null)
    {
        if ($templateMode !== null) {
            $this->view->setTemplateMode($templateMode);
        }

        $doesIt = $this->view->resolveTemplate($templatePath);

        if ($result === false) {
            $this->assertFalse($doesIt);
        } else {
            $this->assertSame(Craft::getAlias($result), $doesIt);
        }
    }

    public function doesTemplateExistData()
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
     * @param $result
     * @param $basePath
     * @param $name
     * @param null $templateExtensions
     * @param null $viewTemplateNameExtensions
     * @dataProvider privateResolveTemplateData
     * @see          testDoesTemplateExistsInSite
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
        $resolved = $this->resolveTemplate(Craft::getAlias($basePath), $name);
        $this->assertSame(Craft::getAlias($result), $resolved);
    }

    public function privateResolveTemplateData()
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
     * Test that Craft::$app->getView()->renderTemplates(); Seems to work correctly with twig. Doesnt impact global props
     * and respects passed in variables.
     *
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function testRenderTemplate()
    {
        // Assert that the _renderingTemplate prop goes in and comes out as null.
        $this->assertSame(null, $this->getInaccessibleProperty($this->view, '_renderingTemplate'));

        $result = $this->view->renderTemplate('withvar', ['name' => 'Giel Tettelaar']);

        $this->assertSame($result, 'Hello iam Giel Tettelaar');
        $this->assertSame(null, $this->getInaccessibleProperty($this->view, '_renderingTemplate'));

        // Test that templates can work without variables.
        $result = $this->view->renderTemplate('novar');

        $this->assertSame($result, 'I have no vars');
    }

    public function testRenderMacro()
    {
        $this->view->setTemplateMode(View::TEMPLATE_MODE_SITE);
        $result = $this->view->renderTemplateMacro('macros', 'testMacro1', ['arg1' => 'Craft', 'arg2' => 'CMS']);
        $this->assertSame('Craft-CMS', $result);
    }

    public function testRenderString()
    {
        $result = $this->view->renderString('{{ arg1 }}-{{ arg2 }}', ['arg1' => 'Craft', 'arg2' => 'CMS']);
        $this->assertSame('Craft-CMS', $result);
    }

    /**
     * @param $result
     * @param $template
     * @param $object
     * @param array $variables
     * @throws Exception
     * @throws \Throwable
     * @dataProvider renderObjectTemplateData
     */
    public function testRenderObjectTemplate($result, $template, $object, array $variables = [])
    {
        $res = $this->view->renderObjectTemplate($template, $object, $variables);
        $this->assertSame($result, $res);
    }

    public function renderObjectTemplateData()
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

            // Test that model params dont overide variable params.
            ['IM DIFFERENTExample Param', '{ exampleParam }{ object.exampleParam }', $model, ['exampleParam' => 'IM DIFFERENT']],
        ];
    }

    public function testSetSiteTemplateMode()
    {
        $genConf = Craft::$app->getConfig()->getGeneral();
        $genConf->defaultTemplateExtensions = ['doStuff', 'random'];
        $genConf->indexTemplateFilenames = ['template', 'raaaa'];

        $this->view->setTemplateMode(View::TEMPLATE_MODE_SITE);
        $this->assertSame(
            Craft::getAlias('@crafttestsfolder/templates'),
            $this->view->templatesPath
        );
        $this->assertSame(
            ['doStuff', 'random'],
            $this->getInaccessibleProperty($this->view, '_defaultTemplateExtensions')
        );

        $this->assertSame(
            ['template', 'raaaa'],
            $this->getInaccessibleProperty($this->view, '_indexTemplateFilenames')
        );
    }
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
    public function testTemplateModeException()
    {
        $this->tester->expectThrowable(Exception::class, function () {
            $this->view->setTemplateMode('i dont exist');
        });
    }

    public function testRegisterTranslations()
    {
        Craft::$app->language = 'nl';

        // Basic test that register translations gets rendered
        $js = $this->generateTranslationJs('app', ["1 month" => "1 maand", "1 minute" => "1 minuut"]);
        $this->assertRegisterJsInputValues($js, View::POS_BEGIN);
        $this->view->registerTranslations('app', ['1 month', '1 minute']);

        // Non existing translations get ignored
        $js = $this->generateTranslationJs('app', ["1 month" => "1 maand"]);
        $this->assertRegisterJsInputValues($js, View::POS_BEGIN);
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
     * @param $result
     * @param $html
     * @param null $namespace
     * @param bool $otherAttributes
     * @dataProvider namespaceInputsData
     */
    public function testNamespaceInputs($result, $html, $namespace = null, $otherAttributes = true)
    {
        $namespaced = $this->view->namespaceInputs($html, $namespace, $otherAttributes);
        $this->assertSame($result, $namespaced);
    }

    public function namespaceInputsData()
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
     * @param $result
     * @param $string
     * @param $namespace
     * @dataProvider namespaceInputNameData
     */
    public function testNamespaceInputName($result, $string, $namespace = null)
    {
        $namespaced = $this->view->namespaceInputName($string, $namespace);
        $this->assertSame($result, $namespaced);
    }
    public function namespaceInputNameData()
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
     *
     * @param $result
     * @param $string
     * @param $namespace
     * @dataProvider namespaceInputIdData
     */
    public function testNamespaceInputId($result, $string, $namespace = null)
    {
        $namespaced = $this->view->namespaceInputId($string, $namespace);
        $this->assertSame($result, $namespaced);
    }
    public function namespaceInputIdData()
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
     * @param $result
     * @param $which
     * @param $rootsToBeAdded
     * @dataProvider getTemplateRootsData
     */
    public function testGetTemplateRoots($result, $which, $rootsToBeAdded)
    {
        Event::on(View::class, View::EVENT_REGISTER_SITE_TEMPLATE_ROOTS, function (RegisterTemplateRootsEvent $event) use ($rootsToBeAdded){
            $event->roots = $rootsToBeAdded;
        });

        $roots = $this->getTemplateRoots($which);
        $this->assertSame($result, $roots);
    }
    public function getTemplateRootsData()
    {
        return [
            [['random-roots' => [null]], 'random-roots', ['random-roots' => null]],
            [['random-roots' => ['/linux/box/craft/templates']], 'random-roots', ['random-roots' => '/linux/box/craft/templates']],
            [['random-roots' => [['windows/box/craft/templates', '/linux/box/craft/templates']]], 'random-roots', ['random-roots' => ['windows/box/craft/templates', '/linux/box/craft/templates']]],
        ];
    }
    /**
     * Testing these events is quite important as they are quite integral to this function working.
     */
    public function testGetTemplateRootsEvents()
    {
        $this->tester->expectEvent(View::class, View::EVENT_REGISTER_CP_TEMPLATE_ROOTS, function () {
            $this->getTemplateRoots('cp');
        });
        $this->tester->expectEvent(View::class, View::EVENT_REGISTER_SITE_TEMPLATE_ROOTS, function () {
            $this->getTemplateRoots('doesnt-matter-what-this-is');
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
        $this->assertRegisterJsInputValues($resultString, View::POS_HEAD);

        $this->registeredJs('randomprop', ['name' => 'value']);
    }

    // Helpers
    // =========================================================================
    private function generateTranslationJs($category, array $messages)
    {
        $category = Json::encode($category);
        $js = '';
        foreach ($messages as $message => $translation) {
            $translation = Json::encode($translation);
            $message = Json::encode($message);
            $js .= ($js !== '' ? "\n" : '') . "Craft.translations[{$category}][{$message}] = {$translation};";
        }

        return <<<JS
if (typeof Craft.translations[{$category}] === 'undefined') {
    Craft.translations[{$category}] = {};
}
{$js}
JS;
    }

    private function assertRegisterJsInputValues($desiredJs, $desiredPosition)
    {
        $this->view = Stub::construct(
            View::class,
            [],
            ['registerJs' => function ($inputJs, $inputPosition) use ($desiredJs, $desiredPosition) {
                $this->assertSame($desiredJs, $inputJs);
                $this->assertSame($desiredPosition, $inputPosition);
            }]
        );
    }

    private function registeredJs($property, $names)
    {
        return $this->invokeMethod($this->view, '_registeredJs', [$property, $names]);
    }
    private function getTemplateRoots($which)
    {
        return $this->invokeMethod($this->view, '_getTemplateRoots', [$which]);
    }
    private function resolveTemplate($basePath, $name)
    {
        return $this->invokeMethod($this->view, '_resolveTemplate', [$basePath, $name]);
    }

}
