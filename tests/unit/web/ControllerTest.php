<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */


namespace craftunit\web;


use Codeception\Test\Unit;
use craft\helpers\UrlHelper;
use craft\test\mockclasses\controllers\TestController;
use craft\web\Response;
use craft\web\View;
use yii\base\Action;
use yii\base\ExitException;
use yiiunit\TestCase;

/**
 * Unit tests for ControllerTest
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.0
 */
class ControllerTest extends Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    /**
     * @var TestController
     */
    private $controller;
    public function _before()
    {
        parent::_before();
        $_SERVER['REQUEST_URI'] = 'https://craftcms.com/admin/dashboard';
        $this->controller = new TestController('test', \Craft::$app);
    }
    public function testBeforeAction()
    {
        $this->tester->expectThrowable(ExitException::class, function () {
            // AllowAnonymous should redirect and Craft::$app->exit(); I.E. An exit exception
            $this->controller->beforeAction(new Action('not-allow-anonymous', $this->controller));
        });

        $this->assertTrue($this->controller->beforeAction(new Action('allow-anonymous', $this->controller)));
    }

    public function testRunActionJsonError()
    {
        // We accept JSON.
        \Craft::$app->getRequest()->setAcceptableContentTypes(['application/json' => true]);
        \Craft::$app->getRequest()->headers->set('Accept', 'application/json');

        /* @var Response $resp */
        $resp = $this->controller->runAction('me-dont-exist');

        // As long as this is set. We can expect yii to do its thing.
        $this->assertSame(Response::FORMAT_JSON, $resp->format);
    }

    public function testTemplateRendering()
    {
        // We need to render a template from the site dir.
        \Craft::$app->getView()->setTemplateMode(View::TEMPLATE_MODE_SITE);

        $response = $this->controller->renderTemplate('template');

        // Again. If this is all good. We can expect Yii to do its thing.
        $this->assertSame('Im a template!', $response->data);
        $this->assertSame(Response::FORMAT_RAW, $response->format);
        $this->assertSame('text/html; charset=UTF-8', $response->getHeaders()->get('content-type'));
    }

    /**
     * If the content-type headers are already set. Render Template should ignore attempting to set them.
     * @throws \yii\base\Exception
     */
    public function testTemplateRenderingIfHeadersAlreadySet()
    {
        // We need to render a template from the site dir.
        \Craft::$app->getView()->setTemplateMode(View::TEMPLATE_MODE_SITE);
        \Craft::$app->getResponse()->getHeaders()->set('content-type', 'HEADERS');

        $response = $this->controller->renderTemplate('template');

        // Again. If this is all good. We can expect Yii to do its thing.
        $this->assertSame('Im a template!', $response->data);
        $this->assertSame(Response::FORMAT_RAW, $response->format);
        $this->assertSame('HEADERS', $response->getHeaders()->get('content-type'));
    }

    private function setMockUser()
    {
        \Craft::$app->getUser()->setIdentity(
            \Craft::$app->getUsers()->getUserById('1')
        );
    }
}