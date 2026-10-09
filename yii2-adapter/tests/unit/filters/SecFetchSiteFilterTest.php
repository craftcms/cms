<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\filters;

use Craft;
use craft\filters\SecFetchSiteFilter;
use craft\test\TestCase;
use craft\web\Request;
use Illuminate\Http\Request as HttpRequest;
use yii\base\Action;
use yii\web\BadRequestHttpException;
use yii\web\Controller;

/**
 * Unit tests for SecFetchSiteFilter
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.10.0
 */
class SecFetchSiteFilterTest extends TestCase
{
    private SecFetchSiteFilter $filter;
    private Action $action;
    private Request $request;

    private HttpRequest $originalRequest;

    protected function setUp(): void
    {
        parent::setUp();

        $controller = $this->createMock(Controller::class);
        $this->action = new Action('test-action', $controller);
        $this->filter = new SecFetchSiteFilter();
        $this->request = Craft::$app->getRequest();
        $this->originalRequest = request();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        app()->bind('request', fn() => $this->originalRequest);
    }

    public function testAllowsSameOriginForUnsafeMethods(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $this->request->getHeaders()->set('Sec-Fetch-Site', 'same-origin');

        self::assertTrue($this->filter->beforeAction($this->action));

        unset($_SERVER['REQUEST_METHOD']);
    }

    public function testAllowsSameSiteWhenConfigured(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $this->request->getHeaders()->set('Sec-Fetch-Site', 'same-site');

        $this->filter->allowSameSite = true;
        self::assertTrue($this->filter->beforeAction($this->action));

        unset($_SERVER['REQUEST_METHOD']);
    }

    public function testAllowsFallbackWhenHeaderMissingAndNotOriginOnly(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $this->request->getHeaders()->remove('Sec-Fetch-Site');

        $this->filter->originOnly = false;
        self::assertTrue($this->filter->beforeAction($this->action));

        unset($_SERVER['REQUEST_METHOD']);
    }

    public function testRejectsWhenOriginOnlyAndHeaderInvalid(): void
    {
        $request = new HttpRequest();
        $request->setMethod('POST');

        app()->bind('request', fn() => $request);

        $this->request->getHeaders()->set('Sec-Fetch-Site', 'cross-site');

        $this->filter->originOnly = true;

        $this->expectException(BadRequestHttpException::class);
        $this->filter->beforeAction($this->action);
    }

    public function testEnforcesWhenCsrfDisabled(): void
    {
        $request = new HttpRequest();
        $request->setMethod('POST');

        app()->bind('request', fn() => $request);

        $this->request->getHeaders()->set('Sec-Fetch-Site', 'cross-site');

        $original = $this->request->enableCsrfValidation;
        $this->request->enableCsrfValidation = false;

        try {
            $this->filter->originOnly = true;
            $this->expectException(BadRequestHttpException::class);
            $this->filter->beforeAction($this->action);
        } finally {
            $this->request->enableCsrfValidation = $original;
        }

        unset($_SERVER['REQUEST_METHOD']);
    }

    public function testSkipsSafeMethods(): void
    {
        $request = new HttpRequest();
        $request->setMethod('GET');

        app()->bind('request', fn() => $request);
        $this->request->getHeaders()->set('Sec-Fetch-Site', 'cross-site');

        $this->filter->originOnly = true;
        self::assertTrue($this->filter->beforeAction($this->action));

        unset($_SERVER['REQUEST_METHOD']);
    }

    public function testInvalidHeaderFallsThroughWhenNotOriginOnly(): void
    {
        $request = new HttpRequest();
        $request->setMethod('POST');

        app()->bind('request', fn() => $request);
        $this->request->getHeaders()->set('Sec-Fetch-Site', 'cross-site');

        $this->filter->originOnly = false;
        self::assertTrue($this->filter->beforeAction($this->action));

        unset($_SERVER['REQUEST_METHOD']);
    }
}
