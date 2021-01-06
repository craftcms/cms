<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\web\twig;

use ArrayObject;
use Codeception\Test\Unit;
use Craft;
use craft\elements\User;
use craft\test\TestSetup;
use craft\web\View;
use crafttests\fixtures\GlobalSetFixture;
use Twig\Error\LoaderError;
use Twig\Error\SyntaxError;
use UnitTester;
use yii\base\ErrorException;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\base\NotSupportedException;
use yii\web\ServerErrorHttpException;

/**
 * Unit tests for the Various functions in the Extension class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2
 */
class ExtensionTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    /**
     * @var View
     */
    protected $view;

    public function _fixtures(): array
    {
        return [
            'globals' => [
                'class' => GlobalSetFixture::class
            ]
        ];
    }

    /**
     * @throws LoaderError
     * @throws SyntaxError
     */
    public function testGlobals()
    {
        // We want web for this part.
        Craft::$app->getRequest()->setIsConsoleRequest(false);
        $user = new User(['firstName' => 'John', 'lastName' => 'Smith']);
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
    public function testCraftSystemGlobals()
    {
        Craft::$app->setEdition(Craft::Pro);
        Craft::$app->getView()->setTemplateMode(View::TEMPLATE_MODE_CP);
        $this->testRenderResult(
            '' . Craft::$app->getEdition() . ' | ' . Craft::Solo . ' | ' . Craft::Pro,
            Craft::$app->getEdition() . ' | 0 | 1'
        );
    }

    /**
     * @throws LoaderError
     * @throws SyntaxError
     */
    public function testGlobalsWithUninstalledCraft()
    {
        Craft::$app->setIsInstalled(false);
        $this->testRenderResult(
            ' |  |  | ',
            '{{ systemName }} | {{ currentSite }} | {{ siteName }} | {{ siteUrl }}'
        );
    }

    /**
     * @throws LoaderError
     * @throws SyntaxError
     * @throws ErrorException
     * @throws Exception
     * @throws NotSupportedException
     * @throws ServerErrorHttpException
     */
    public function testSiteGlobals()
    {
        Craft::$app->getProjectConfig()->set('system.name', 'Im a test system');
        $this->testRenderResult(
            'Im a test system | default Craft test site ' . TestSetup::SITE_URL,
            '{{ systemName }} | {{ currentSite.handle }} {{ currentSite }} {{ siteUrl }}'
        );
    }

    /**
     * @throws LoaderError
     * @throws SyntaxError
     * @throws Exception
     */
    public function testElementGlobals()
    {
        Craft::$app->getView()->setTemplateMode(View::TEMPLATE_MODE_SITE);

        $this->testRenderResult(
            'A global set | A different global set',
            '{{ aGlobalSet }} | {{ aDifferentGlobalSet }}'
        );
    }

    /**
     * @throws LoaderError
     * @throws SyntaxError
     */
    public function testCsrfInputFunction()
    {
        Craft::$app->getConfig()->getGeneral()->enableCsrfProtection = true;
        $this->testRenderResult(
            '<input type="hidden" name="CRAFT_CSRF_TOKEN" value="' . Craft::$app->getRequest()->getCsrfToken() . '">',
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
    public function testRedirectInputFunction()
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
    public function testActionInputFunction()
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
    public function testRenderObjectTemplateFunction()
    {
        // This is some next level inception stuff IMO.....
        $this->testRenderResult(
            'John',
            '{{ renderObjectTemplate("{{ object.firstName}}", {firstName: "John"}) }}'
        );
    }

    public function testExpression()
    {
        $this->testRenderResult(
            'Im an expression | var | Im an expression',
            '{% set expression =  expression("Im an expression", ["var"]) %}{{ expression }} | {{ expression.params[0] }} | {{ expression.expression }}'
        );
    }

    /**
     * @throws LoaderError
     * @throws SyntaxError
     */
    public function testGetenvFunction()
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
    public function testParseEnvFunction()
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
     * @throws LoaderError
     * @throws SyntaxError
     */
    public function testIndexOfFilter()
    {
        $arrayObject = new ArrayObject(['John', 'Smith']);

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
            '{{ ArrayObject|indexOf("Smith") }}',
            ['ArrayObject' => $arrayObject]
        );
    }

    /**
     * @throws LoaderError
     * @throws SyntaxError
     */
    public function testShuffleFunction()
    {
        // 1 means true (string version of bool)
        $this->testRenderResult(
            '1',
            '{% set a = [0,1,2,3,4,5,6,7,8,9,"a","b","c","d","e","f"] %}{{ a != shuffle(a) or a != shuffle(a) }}'
        );
    }

    /**
     * @throws LoaderError
     * @throws SyntaxError
     */
    public function testWithoutFilter()
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
    public function testWithoutKeyFilter()
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

    /**
     * @param string $expectedString
     * @param string $renderString
     * @param array $variables
     * @throws LoaderError
     * @throws SyntaxError
     */
    protected function testRenderResult(string $expectedString, string $renderString, array $variables = [])
    {
        $result = $this->view->renderString($renderString, $variables);
        self::assertSame(
            $expectedString,
            $result
        );
    }

    /**
     * @inheritdoc
     */
    protected function _before()
    {
        parent::_before();

        $this->view = Craft::$app->getView();
    }
}
