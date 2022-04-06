<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\web;

use Codeception\Test\Unit;
use Craft;
use craft\test\mockclasses\controllers\TestController;
use craft\test\TestCase;
use craft\test\TestSetup;
use craft\web\Response;
use craft\web\TemplateResponseFormatter;
use craft\web\View;
use UnitTester;
use yii\base\Action;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;

/**
 * Unit tests for Controller
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2
 */
class ControllerTest extends TestCase
{
    /**
     * @var UnitTester
     */
    protected UnitTester $tester;

    /**
     * @var TestController
     */
    private TestController $controller;

    /**
     *
     */
    public function testBeforeAction(): void
    {
        Craft::$app->getConfig()->getGeneral()->isSystemLive = true;

        $this->tester->expectThrowable(ForbiddenHttpException::class, function() {
            // AllowAnonymous should redirect and Craft::$app->exit(); I.E. An exit exception
            $this->controller->beforeAction(new Action('not-allow-anonymous', $this->controller));
        });

        self::assertTrue($this->controller->beforeAction(new Action('allow-anonymous', $this->controller)));
    }

    /**
     * @throws Exception
     */
    public function testTemplateRendering(): void
    {
        // We need to render a template from the site dir.
        Craft::$app->getView()->setTemplateMode(View::TEMPLATE_MODE_SITE);

        $response = $this->controller->renderTemplate('template');
        (new TemplateResponseFormatter())->format($response);

        // Again. If this is all good. We can expect Yii to do its thing.
        self::assertSame('Im a template!', $response->content);
        self::assertSame(TemplateResponseFormatter::FORMAT, $response->format);
        self::assertSame('text/html; charset=UTF-8', $response->getHeaders()->get('content-type'));
    }

    /**
     * If the content-type headers are already set. Render Template should ignore attempting to set them.
     *
     * @throws Exception
     */
    public function testTemplateRenderingIfHeadersAlreadySet(): void
    {
        // We need to render a template from the site dir.
        Craft::$app->getView()->setTemplateMode(View::TEMPLATE_MODE_SITE);
        Craft::$app->getResponse()->getHeaders()->set('content-type', 'HEADERS');

        $response = $this->controller->renderTemplate('template');
        (new TemplateResponseFormatter())->format($response);

        // Again. If this is all good. We can expect Yii to do its thing.
        self::assertSame('Im a template!', $response->content);
        self::assertSame(TemplateResponseFormatter::FORMAT, $response->format);
        self::assertSame('HEADERS', $response->getHeaders()->get('content-type'));
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws BadRequestHttpException
     */
    public function testRedirectToPostedUrl(): void
    {
        $redirect = Craft::$app->getSecurity()->hashData('craft/do/stuff');

        // Default
        $default = $this->controller->redirectToPostedUrl();

        // Test that with nothing passed in. It defaults to the base. See self::getBaseUrlForRedirect() for more info.
        self::assertSame(TestSetup::SITE_URL, $default->headers->get('Location'));

        // What happens when we pass in a param.
        Craft::$app->getRequest()->setBodyParams(['redirect' => $redirect]);
        $default = $this->controller->redirectToPostedUrl();
        self::assertSame(TestSetup::SITE_URL . 'craft/do/stuff', $default->headers->get('Location'));
    }

    /**
     * @throws BadRequestHttpException
     */
    public function testRedirectToPostedWithSetDefault(): void
    {
        $withDefault = $this->controller->redirectToPostedUrl(null, 'craft/do/stuff');
        self::assertSame(TestSetup::SITE_URL . 'craft/do/stuff', $withDefault->headers->get('Location'));
    }

    /**
     *
     */
    public function testAsJsonP(): void
    {
        $result = $this->controller->asJsonP(['test' => 'test']);
        self::assertSame(Response::FORMAT_JSONP, $result->format);
        self::assertSame(['test' => 'test'], $result->data);
    }

    /**
     *
     */
    public function testAsRaw(): void
    {
        $result = $this->controller->asRaw(['test' => 'test']);
        self::assertSame(Response::FORMAT_RAW, $result->format);
        self::assertSame(['test' => 'test'], $result->data);
    }

    /**
     *
     */
    public function testAsErrorJson(): void
    {
        $result = $this->controller->asErrorJson('im an error');
        self::assertSame(Response::FORMAT_JSON, $result->format);
        self::assertSame(['error' => 'im an error'], $result->data);
    }

    /**
     *
     */
    public function testRedirect(): void
    {
        self::assertSame(TestSetup::SITE_URL . 'do/stuff', $this->controller->redirect('do/stuff')->headers->get('Location'));

        // We dont use _getBaseUrlForRedirect because the :port80 wont work with urlWithScheme.
        self::assertSame(rtrim(TestSetup::SITE_URL, '/') . ':80/', $this->controller->redirect(null)->headers->get('Location'));

        // Absolute url
        self::assertSame(
            'https://craftcms.com',
            $this->controller->redirect('https://craftcms.com')->headers->get('Location')
        );

        // Custom status code
        self::assertSame(
            500,
            $this->controller->redirect('https://craftcms.com', 500)->statusCode
        );
    }

    /**
     * @inheritdoc
     */
    protected function _before(): void
    {
        parent::_before();
        $_SERVER['REQUEST_URI'] = 'https://craftcms.com/admin/dashboard';
        $this->controller = new TestController('test', Craft::$app);
    }
}
