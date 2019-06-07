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

    public function testCraftSystemGlobals()
    {
        Craft::$app->setEdition(Craft::Pro);
        Craft::$app->getView()->setTemplateMode(View::TEMPLATE_MODE_CP);
        $this->extensionRenderTest(
            '{{ CraftEdition }} | {{ CraftSolo }} | {{ CraftPro }}',
            ''.Craft::$app->getEdition().' | '. Craft::Solo . ' | '. Craft::Pro
        );
    }

    public function testSiteGlobals()
    {
        Craft::$app->getProjectConfig()->set('system.name', 'Im a test system');
        $this->extensionRenderTest(
            '{{ systemName }} | {{ currentSite.handle }} {{ currentSite }} {{ siteUrl }}',
            'Im a test system | default Craft test site https://test.craftcms.test/'
        );
    }

    public function testElementGlobals()
    {
        Craft::$app->getView()->setTemplateMode(View::TEMPLATE_MODE_SITE);

        $this->extensionRenderTest(
            '{{ aGlobalSet }} | {{ aDifferentGlobalSet }}',
            'A global set | A different global set'
        );
    }

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

    public function testGetEnv()
    {
        $this->extensionRenderTest(
            '{{ getenv("FROM_EMAIL_NAME") }} | {{ getenv("FROM_EMAIL_ADDRESS") }}',
            'Craft CMS | info@craftcms.com'
        );
    }

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


    // Protected Methods
    // =========================================================================

    /**
     * @param string $renderString
     * @param string $expectedString
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\SyntaxError
     */
    protected function extensionRenderTest(string $renderString, string $expectedString)
    {
        $result = $this->view->renderString($renderString);
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
