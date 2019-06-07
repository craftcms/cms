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
        $this->globalExtensionRenderTest(
            '{{ currentUser.firstName }} | {{ currentUser.lastName }}',
            'John | Smith'
        );

        // Craft variable - poke various calls.
        $this->globalExtensionRenderTest(
            '{{ craft.app.user.getIdentity().firstName }}',
            'John'
        );

        $this->globalExtensionRenderTest(
            '{{ craft.app.request.getRawBody() }}',
            'This is a raw body'
        );
    }

    public function testCraftSystemGlobals()
    {
        Craft::$app->setEdition(Craft::Pro);
        Craft::$app->getView()->setTemplateMode(View::TEMPLATE_MODE_CP);
        $this->globalExtensionRenderTest(
            '{{ CraftEdition }} | {{ CraftSolo }} | {{ CraftPro }}',
            ''.Craft::$app->getEdition().' | '. Craft::Solo . ' | '. Craft::Pro
        );
    }

    public function testSiteGlobals()
    {
        Craft::$app->getProjectConfig()->set('system.name', 'Im a test system');
        $this->globalExtensionRenderTest(
            '{{ systemName }} | {{ currentSite.handle }} {{ currentSite }} {{ siteUrl }}',
            'Im a test system | default Craft test site https://test.craftcms.test/'
        );
    }

    public function testElementGlobals()
    {
        Craft::$app->getView()->setTemplateMode(View::TEMPLATE_MODE_SITE);

        $this->globalExtensionRenderTest(
            '{{ aGlobalSet }} | {{ aDifferentGlobalSet }}',
            'A global set | A different global set'
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
    protected function globalExtensionRenderTest(string $renderString, string $expectedString)
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
