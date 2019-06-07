<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\services;

use Codeception\Test\Unit;
use Craft;
use craft\elements\User;
use craft\web\View;
use crafttests\fixtures\GlobalSetFixture;
use UnitTester;
use craft\web\twig\Extension;
use ArrayObject;

/**
 * Unit tests for the Various functions in the Extension class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2
 */
class ExtensionTest extends Unit
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

    public function _fixtures()
    {
        return [
            'globals' => [
                'class' => GlobalSetFixture::class
            ]
        ];
    }

    // Tests
    // =========================================================================

    /**
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\SyntaxError
     */
    public function testGlobals()
    {
        // We want web for this part.
        Craft::$app->getRequest()->setIsConsoleRequest(false);
        $user = new User(['firstName' => 'John', 'lastName' => 'Smith']);
        Craft::$app->getUser()->setIdentity($user);
        Craft::$app->getRequest()->setRawBody('This is a raw body');

        // Current user
        $this->extensionRenderTest(
            '{{ currentUser.firstName }} | {{ currentUser.lastName }}',
            'John | Smith'
        );

        // Craft variable - poke various calls.
        $this->extensionRenderTest(
            '{{ craft.app.user.getIdentity().firstName }}',
            'John'
        );

        $this->extensionRenderTest(
            '{{ craft.app.request.getRawBody() }}',
            'This is a raw body'
        );
    }

    /**
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\SyntaxError
     * @throws \yii\base\Exception
     */
    public function testCraftSystemGlobals()
    {
        Craft::$app->setEdition(Craft::Pro);
        Craft::$app->getView()->setTemplateMode(View::TEMPLATE_MODE_CP);
        $this->extensionRenderTest(
            '{{ CraftEdition }} | {{ CraftSolo }} | {{ CraftPro }}',
            ''.Craft::$app->getEdition().' | '. Craft::Solo . ' | '. Craft::Pro
        );
    }

    /**
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\SyntaxError
     */
    public function testGlobalsWithUninstalledCraft()
    {
        Craft::$app->setIsInstalled(false);
        $this->extensionRenderTest(
            '{{ systemName }} | {{ currentSite }} | {{ siteName }} | {{ siteUrl }}',
            ' |  |  | '
        );

    }

    /**
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\SyntaxError
     * @throws \yii\base\ErrorException
     * @throws \yii\base\Exception
     * @throws \yii\base\NotSupportedException
     * @throws \yii\web\ServerErrorHttpException
     */
    public function testSiteGlobals()
    {
        Craft::$app->getProjectConfig()->set('system.name', 'Im a test system');
        $this->extensionRenderTest(
            '{{ systemName }} | {{ currentSite.handle }} {{ currentSite }} {{ siteUrl }}',
            'Im a test system | default Craft test site https://test.craftcms.test/'
        );
    }

    /**
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\SyntaxError
     * @throws \yii\base\Exception
     */
    public function testElementGlobals()
    {
        Craft::$app->getView()->setTemplateMode(View::TEMPLATE_MODE_SITE);

        $this->extensionRenderTest(
            '{{ aGlobalSet }} | {{ aDifferentGlobalSet }}',
            'A global set | A different global set'
        );
    }

    /**
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\SyntaxError
     */
    public function testCsrfInput()
    {
        Craft::$app->getConfig()->getGeneral()->enableCsrfProtection = false;
        $this->extensionRenderTest('{{ csrfInput() }}', '');

        Craft::$app->getConfig()->getGeneral()->enableCsrfProtection = true;
        $this->extensionRenderTest(
            '{{ csrfInput() }}',
            '<input type="hidden" name="CRAFT_CSRF_TOKEN" value="'.Craft::$app->getRequest()->getCsrfToken().'">'
        );

        // Custom name - just to be sure.
        Craft::$app->getConfig()->getGeneral()->csrfTokenName = 'HACKER_POOF';
        $this->extensionRenderTest(
            '{{ csrfInput() }}',
            '<input type="hidden" name="HACKER_POOF" value="'.Craft::$app->getRequest()->getCsrfToken().'">'
        );
    }

    /**
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\SyntaxError
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     */
    public function testRedirectInput()
    {
        $this->extensionRenderTest(
            '{{ redirectInput("A URL") }}',
            '<input type="hidden" name="redirect" value="'.Craft::$app->getSecurity()->hashData('A URL').'">'
        );

        $this->extensionRenderTest(
            '{{ redirectInput("A URL WITH CHARS !@#$%^&*()😋") }}',
            '<input type="hidden" name="redirect" value="'.Craft::$app->getSecurity()->hashData('A URL WITH CHARS !@#$%^&*()😋').'">'
        );
    }

    /**
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\SyntaxError
     */
    public function testActionInput()
    {
        $this->extensionRenderTest(
            '{{ actionInput("A URL") }}',
            '<input type="hidden" name="action" value="A URL">'
        );

        $this->extensionRenderTest(
            '{{ actionInput("A URL WITH CHARS !@#$%^&*()😋") }}',
            '<input type="hidden" name="action" value="A URL WITH CHARS !@#$%^&*()😋">'
        );
    }

    /**
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\SyntaxError
     */
    public function testRenderObjectTemplate()
    {
        // This is some next level inception stuff IMO.....
        $this->extensionRenderTest(
            '{{ renderObjectTemplate("{{ object.firstName}}", {firstName: "John"}) }}',
            'John'
        );
    }

    public function testExpression()
    {
        $this->extensionRenderTest(
            '{% set expression =  expression("Im an expression", ["var"]) %}{{ expression }} | {{ expression.params[0] }} | {{ expression.expression }}',
            'Im an expression | var | Im an expression'
        );
    }

    /**
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\SyntaxError
     */
    public function testGetEnv()
    {
        $this->extensionRenderTest(
            '{{ getenv("FROM_EMAIL_NAME") }} | {{ getenv("FROM_EMAIL_ADDRESS") }}',
            'Craft CMS | info@craftcms.com'
        );
    }

    /**
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\SyntaxError
     */
    public function testEnvParsing()
    {
        $this->extensionRenderTest(
            '{{ parseEnv("$FROM_EMAIL_NAME") }}',
            'Craft CMS'
        );

        $this->extensionRenderTest(
            '{{ parseEnv("FROM_EMAIL_NAME") }}',
            'FROM_EMAIL_NAME'
        );
    }

    /**
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\SyntaxError
     */
    public function testIndexOf()
    {
        $arrayObject = new ArrayObject(['John', 'Smith']);

        $this->extensionRenderTest(
            '{{ "Im a string"|indexOf("a") }}',
            '3'
        );

        $this->extensionRenderTest(
            '{{ [2, 3, 4, 5]|indexOf(3) }}',
            '1'
        );

        $this->extensionRenderTest(
            '{{ ArrayObject|indexOf("Smith") }}',
            '1',
            ['ArrayObject' => $arrayObject]
        );
    }
    
    // Protected Methods
    // =========================================================================

    /**
     * @param string $renderString
     * @param string $expectedString
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\SyntaxError
     */
    protected function extensionRenderTest(string $renderString, string $expectedString, array $variables = [])
    {
        $result = $this->view->renderString($renderString, $variables);
        $this->assertSame(
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
