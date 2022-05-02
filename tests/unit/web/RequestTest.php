<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\web;

use Craft;
use craft\models\Site;
use craft\services\Sites;
use craft\test\TestCase;
use craft\web\Request;
use crafttests\fixtures\SitesFixture;
use ReflectionException;
use UnitTester;
use yii\web\BadRequestHttpException;

/**
 * Unit tests for Request
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2
 */
class RequestTest extends TestCase
{
    /**
     * @var Request
     */
    protected Request $request;

    /**
     * @var UnitTester
     */
    protected UnitTester $tester;

    public function _fixtures(): array
    {
        return [
            'sites' => [
                'class' => SitesFixture::class,
            ],
        ];
    }

    /**
     *
     */
    public function testInit(): void
    {
        $oldServer = $_SERVER;

        // Site request: Craft installed at web root
        $_SERVER = array_merge($oldServer, [
            'REQUEST_URI' => '/foo/bar/baz',
            'SCRIPT_NAME' => '/index.php',
            'SERVER_NAME' => 'craft.test',
        ]);
        $request = new Request();
        self::assertEquals(false, $request->getIsCpRequest());
        self::assertEquals('foo/bar/baz', $request->getPathInfo());

        // Site request without base URI: Craft installed at web root
        $_SERVER = array_merge($oldServer, [
            'REQUEST_URI' => '/foo/bar/baz',
            'SCRIPT_NAME' => '/index.php',
            'SERVER_NAME' => 'craft.test',
        ]);
        $sites = new Sites();
        $sites->setCurrentSite(new Site([
            'language' => 'en-US',
            'baseUrl' => 'http://craft.test/foo',
        ]));
        $request = new Request([
            'isCpRequest' => false,
            'sites' => $sites,
        ]);
        self::assertEquals(false, $request->getIsCpRequest());
        self::assertEquals('bar/baz', $request->getPathInfo());

        // Implicit control panel request: Craft installed at web root
        $_SERVER = array_merge($oldServer, [
            'REQUEST_URI' => '/foo/bar/baz',
            'SCRIPT_NAME' => '/index.php',
            'SERVER_NAME' => 'craft.test',
        ]);
        $generalConfig = clone Craft::$app->getConfig()->getGeneral();
        $generalConfig->cpTrigger = 'foo';
        $request = new Request([
            'generalConfig' => $generalConfig,
        ]);
        self::assertEquals(true, $request->getIsCpRequest());
        self::assertEquals('bar/baz', $request->getPathInfo());

        // Explicit control panel request with control panel trigger: Craft installed at web root
        $_SERVER = array_merge($oldServer, [
            'REQUEST_URI' => '/foo/bar/baz',
            'SCRIPT_NAME' => '/index.php',
            'SERVER_NAME' => 'craft.test',
        ]);
        $generalConfig = clone Craft::$app->getConfig()->getGeneral();
        $generalConfig->cpTrigger = 'foo';
        $request = new Request([
            'isCpRequest' => true,
            'generalConfig' => $generalConfig,
        ]);
        self::assertEquals(true, $request->getIsCpRequest());
        self::assertEquals('bar/baz', $request->getPathInfo());

        // Explicit control panel request without control panel trigger: Craft installed at web root
        $_SERVER = array_merge($oldServer, [
            'REQUEST_URI' => '/foo/bar/baz',
            'SCRIPT_NAME' => '/index.php',
            'SERVER_NAME' => 'craft.test',
        ]);
        $generalConfig = clone Craft::$app->getConfig()->getGeneral();
        $generalConfig->cpTrigger = null;
        $request = new Request([
            'isCpRequest' => true,
            'generalConfig' => $generalConfig,
        ]);
        self::assertEquals(true, $request->getIsCpRequest());
        self::assertEquals('foo/bar/baz', $request->getPathInfo());

        // Site request: Craft installed in subfolder
        // https://github.com/craftcms/cms/issues/6579
        $_SERVER = array_merge($oldServer, [
            'REQUEST_URI' => '/foo/bar/baz',
            'SCRIPT_NAME' => '/foo/index.php',
            'SERVER_NAME' => 'craft.test',
        ]);
        $request = new Request();
        self::assertEquals(false, $request->getIsCpRequest());
        self::assertEquals('bar/baz', $request->getPathInfo());

        // Site request without base URI: Craft installed in subfolder
        // https://github.com/craftcms/cms/issues/6579
        $_SERVER = array_merge($oldServer, [
            'REQUEST_URI' => '/foo/bar/baz',
            'SCRIPT_NAME' => '/foo/index.php',
            'SERVER_NAME' => 'craft.test',
        ]);
        $sites = new Sites();
        $sites->setCurrentSite(new Site([
            'language' => 'en-US',
            'baseUrl' => 'http://craft.test/foo/bar',
        ]));
        $request = new Request([
            'isCpRequest' => false,
            'sites' => $sites,
        ]);
        self::assertEquals(false, $request->getIsCpRequest());
        self::assertEquals('baz', $request->getPathInfo());

        // Implicit control panel request: Craft installed in subfolder
        $_SERVER = array_merge($oldServer, [
            'REQUEST_URI' => '/foo/bar/baz',
            'SCRIPT_NAME' => '/foo/index.php',
            'SERVER_NAME' => 'craft.test',
        ]);
        $generalConfig = clone Craft::$app->getConfig()->getGeneral();
        $generalConfig->cpTrigger = 'bar';
        $request = new Request([
            'generalConfig' => $generalConfig,
        ]);
        self::assertEquals(true, $request->getIsCpRequest());
        self::assertEquals('baz', $request->getPathInfo());

        // Explicit control panel request with control panel trigger: Craft installed in subfolder
        $_SERVER = array_merge($oldServer, [
            'REQUEST_URI' => '/foo/bar/baz',
            'SCRIPT_NAME' => '/foo/index.php',
            'SERVER_NAME' => 'craft.test',
        ]);
        $generalConfig = clone Craft::$app->getConfig()->getGeneral();
        $generalConfig->cpTrigger = 'bar';
        $request = new Request([
            'isCpRequest' => true,
            'generalConfig' => $generalConfig,
        ]);
        self::assertEquals(true, $request->getIsCpRequest());
        self::assertEquals('baz', $request->getPathInfo());

        // Explicit control panel request without control panel trigger: Craft installed in subfolder
        $_SERVER = array_merge($oldServer, [
            'REQUEST_URI' => '/foo/bar/baz',
            'SCRIPT_NAME' => '/foo/index.php',
            'SERVER_NAME' => 'craft.test',
        ]);
        $generalConfig = clone Craft::$app->getConfig()->getGeneral();
        $generalConfig->cpTrigger = null;
        $request = new Request([
            'isCpRequest' => true,
            'generalConfig' => $generalConfig,
        ]);
        self::assertEquals(true, $request->getIsCpRequest());
        self::assertEquals('bar/baz', $request->getPathInfo());

        $_SERVER = $oldServer;
    }

    /**
     * @dataProvider isMobileBrowserDataProvider
     * @param bool $expected
     * @param string $userAgent
     * @param bool $detectTablets
     */
    public function testIsMobileBrowser(bool $expected, string $userAgent, bool $detectTablets = false): void
    {
        $this->request->getHeaders()->set('User-Agent', $userAgent);
        self::assertSame($expected, $this->request->isMobileBrowser($detectTablets));
    }

    /**
     * @throws BadRequestHttpException
     */
    public function testGetRequiredParam(): void
    {
        $this->request->setBodyParams(['test' => 'RAAA']);
        self::assertSame('RAAA', $this->request->getRequiredParam('test'));

        $this->tester->expectThrowable(BadRequestHttpException::class, function() {
            $this->request->getRequiredParam('not-a-param');
        });
    }

    /**
     *
     */
    public function testGetParamWithBody(): void
    {
        $this->request->setBodyParams(['bodyTest' => 'RAAA']);
        self::assertSame('RAAA', $this->request->getParam('bodyTest'));
    }

    /**
     *
     */
    public function testGetParamWithQuery(): void
    {
        $this->request->setQueryParams(['queryTest' => 'RAAA']);
        self::assertSame('RAAA', $this->request->getParam('queryTest'));
    }

    /**
     *
     */
    public function testGetParamDefault(): void
    {
        self::assertSame('default', $this->request->getParam('not-a-param', 'default'));
    }

    /**
     * @throws BadRequestHttpException
     */
    public function testGetRequiredQueryParam(): void
    {
        $this->request->setBodyParams(['bodyTest' => 'RAAA']);
        $this->tester->expectThrowable(BadRequestHttpException::class, function() {
            $this->request->getRequiredQueryParam('bodyTest');
        });

        $this->request->setQueryParams(['queryTest' => 'RAAA']);
        self::assertSame('RAAA', $this->request->getRequiredQueryParam('queryTest'));
    }

    /**
     * @throws BadRequestHttpException
     */
    public function testGetRequiredBodyParam(): void
    {
        $this->request->setQueryParams(['queryTest' => 'RAAA']);
        $this->tester->expectThrowable(BadRequestHttpException::class, function() {
            $this->request->getRequiredBodyParam('queryTest');
        });

        $this->request->setBodyParams(['bodyTest' => 'RAAA']);
        self::assertSame('RAAA', $this->request->getRequiredBodyParam('bodyTest'));
    }

    /**
     * @dataProvider getUserIpDataProvider
     * @param string|null $expected
     * @param string|null $headerName
     * @param string|null $headerValue
     * @param int $filterOptions
     */
    public function testGetUserIp(?string $expected, ?string $headerName, ?string $headerValue, int $filterOptions = 0): void
    {
        if ($headerName !== null) {
            $this->request->getHeaders()->set($headerName, $headerValue);
        }

        self::assertSame($expected, $this->request->getUserIP($filterOptions));
    }

    /**
     * @dataProvider getClientOsDataProvider
     * @param string $expected
     * @param string $userAgent
     */
    public function testGetClientOs(string $expected, string $userAgent): void
    {
        $this->request->getHeaders()->set('User-Agent', $userAgent);
        self::assertSame($expected, $this->request->getClientOs());
    }

    /**
     *
     */
    public function testGetCsrfToken(): void
    {
        $token = $this->request->getCsrfToken();

        $otherToken = $this->request->getCsrfToken();
        self::assertSame($token, $otherToken);

        self::assertNotSame($token, $this->request->getCsrfToken(true));
    }

    /**
     *
     */
    public function testGenerateCsrfToken(): void
    {
        $token = $this->_generateCsrfToken();
        self::assertSame(40, strlen($token));

        $this->_setMockUser();
        $newToken = $this->_generateCsrfToken();
        $tokenComponents = explode('|', $newToken);

        self::assertNotSame($newToken, $token);

        // Ensure that the data we want exists and is according to our desired specs
        self::assertSame('1', $tokenComponents['2']);
        self::assertSame(40, strlen($tokenComponents['0']));
    }

    /**
     *
     */
    public function testCsrfTokenValidForCurrentUser(): void
    {
        $this->_setMockUser();
        $token = $this->_generateCsrfToken();

        self::assertTrue($this->_isCsrfValidForUser($token));
    }

    /**
     *
     */
    public function testCsrfTokenValidFailure(): void
    {
        $token = $this->_generateCsrfToken();

        self::assertTrue($this->_isCsrfValidForUser($token));
        self::assertTrue($this->_isCsrfValidForUser('RANDOM'));
    }

    /**
     * @dataProvider getParamDataProvider
     * @param mixed $expected
     * @param mixed $defaultValue
     * @param array $params
     * @param string|null $name
     * @throws ReflectionException
     */
    public function testGetParam(mixed $expected, mixed $defaultValue, array $params, ?string $name): void
    {
        self::assertSame($expected, $this->_getParam($name, $defaultValue, $params));
    }

    /**
     *
     */
    public function testCheckRequestTypeWithTokenParam(): void
    {
        $this->request->setBodyParams([Craft::$app->getConfig()->getGeneral()->tokenParam => 'something']);
        $this->request->checkIfActionRequest(true);

        self::assertTrue($this->getInaccessibleProperty($this->request, '_checkedRequestType'));
        self::assertFalse($this->getInaccessibleProperty($this->request, '_isActionRequest'));
    }

    /**
     * https://deviceatlas.com/blog/list-of-user-agent-strings
     *
     * @return array
     */
    public function isMobileBrowserDataProvider(): array
    {
        return [
            [true, 'Mozilla/5.0 (Linux; Android 7.0; SM-G892A Build/NRD90M; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/60.0.3112.107 Mobile Safari/537.36'],
            [true, 'Mozilla/5.0 (Linux; Android 6.0.1; SM-G935S Build/MMB29K; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/55.0.2883.91 Mobile Safari/537.36'],
            [true, 'Mozilla/5.0 (Linux; Android 6.0.1; SM-G920V Build/MMB29K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/52.0.2743.98 Mobile Safari/537.36'],
            [true, 'Mozilla/5.0 (Linux; Android 5.1.1; SM-G928X Build/LMY47X) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/47.0.2526.83 Mobile Safari/537.36'],
            [true, 'Mozilla/5.0 (Linux; Android 6.0.1; Nexus 6P Build/MMB29P) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/47.0.2526.83 Mobile Safari/537.36'],
            [true, 'Mozilla/5.0 (Linux; Android 6.0; HTC One X10 Build/MRA58K; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/61.0.3163.98 Mobile Safari/537.36'],
            [true, 'Mozilla/5.0 (iPhone; CPU iPhone OS 12_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/12.0 Mobile/15E148 Safari/604.1'],
            [true, 'Mozilla/5.0 (iPhone; CPU iPhone OS 12_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/12.0 Mobile/15E148 Safari/604.1'],
            [true, 'Mozilla/5.0 (iPhone; CPU iPhone OS 12_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) FxiOS/13.2b11866 Mobile/16A366 Safari/605.1.15'],
            [true, 'Mozilla/5.0 (iPhone; CPU iPhone OS 11_0 like Mac OS X) AppleWebKit/604.1.38 (KHTML, like Gecko) Version/11.0 Mobile/15A5370a Safari/604.1'],
            [true, 'Mozilla/5.0 (Windows Phone 10.0; Android 6.0.1; Microsoft; RM-1152) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/52.0.2743.116 Mobile Safari/537.36 Edge/15.15254'],
            [true, 'Mozilla/5.0 (Windows Phone 10.0; Android 4.2.1; Microsoft; RM-1127_16056) AppleWebKit/537.36(KHTML, like Gecko) Chrome/42.0.2311.135 Mobile Safari/537.36 Edge/12.10536'],
            [true, 'Mozilla/5.0 (Windows Phone 10.0; Android 4.2.1; Microsoft; Lumia 950) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/46.0.2486.0 Mobile Safari/537.36 Edge/13.1058'],

            // TABLETS. With detection off.
            [false, 'Mozilla/5.0 (Linux; Android 7.0; Pixel C Build/NRD90M; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/52.0.2743.98 Safari/537.36'],
            [false, 'Mozilla/5.0 (Linux; Android 6.0.1; SGP771 Build/32.2.A.0.253; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/52.0.2743.98 Safari/537.36'],
            [false, 'Mozilla/5.0 (Linux; Android 6.0.1; SHIELD Tablet K1 Build/MRA58K; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/55.0.2883.91 Safari/537.36'],
            [false, 'Mozilla/5.0 (Linux; Android 5.0.2; SAMSUNG SM-T550 Build/LRX22G) AppleWebKit/537.36 (KHTML, like Gecko) SamsungBrowser/3.3 Chrome/38.0.2125.102 Safari/537.36'],
            [false, 'Mozilla/5.0 (Linux; Android 4.4.3; KFTHWI Build/KTU84M) AppleWebKit/537.36 (KHTML, like Gecko) Silk/47.1.79 like Chrome/47.0.2526.80 Safari/537.36'],

            // TABLETS. With detection on.
            [true, 'Mozilla/5.0 (Linux; Android 7.0; Pixel C Build/NRD90M; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/52.0.2743.98 Safari/537.36', true],
            [true, 'Mozilla/5.0 (Linux; Android 6.0.1; SGP771 Build/32.2.A.0.253; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/52.0.2743.98 Safari/537.36', true],
            [true, 'Mozilla/5.0 (Linux; Android 6.0.1; SHIELD Tablet K1 Build/MRA58K; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/55.0.2883.91 Safari/537.36', true],
            [true, 'Mozilla/5.0 (Linux; Android 5.0.2; SAMSUNG SM-T550 Build/LRX22G) AppleWebKit/537.36 (KHTML, like Gecko) SamsungBrowser/3.3 Chrome/38.0.2125.102 Safari/537.36', true],
            [true, 'Mozilla/5.0 (Linux; Android 4.4.3; KFTHWI Build/KTU84M) AppleWebKit/537.36 (KHTML, like Gecko) Silk/47.1.79 like Chrome/47.0.2526.80 Safari/537.36', true],

            // DESKTOP/Laptop
            [false, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/42.0.2311.135 Safari/537.36 Edge/12.246', true],
            [false, 'Mozilla/5.0 (X11; CrOS x86_64 8172.45.0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/51.0.2704.64 Safari/537.36', true],
            [false, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_2) AppleWebKit/601.3.9 (KHTML, like Gecko) Version/9.0.2 Safari/601.3.9', true],
            [false, 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/47.0.2526.111 Safari/537.36', true],
        ];
    }

    /**
     * @return array
     */
    public function getUserIpDataProvider(): array
    {
        return [
            ['123.123.123.123', 'Client-IP', '123.123.123.123'],
            ['123.123.123.123', 'X-Forwarded-For', '123.123.123.123'],
            ['123.123.123.123', 'X-Cluster-Client-IP', '123.123.123.123'],
            ['123.123.123.123', 'Forwarded-For', '123.123.123.123'],
            ['123.123.123.123', 'Forwarded', '123.123.123.123'],
            ['1.1.1.1', null, null],

            [null, 'Client-IP', '123.123.123.123', FILTER_FLAG_IPV6],
            ['1.1.1.1', 'Client-IP', '2001:0db8:85a3:0000:0000:8a2e:0370:7334', FILTER_FLAG_IPV4],
            ['1.1.1.1', 'Client-IP', '172.16.0.0/12', FILTER_FLAG_NO_PRIV_RANGE],
            ['1.1.1.1', 'Client-IP', '0.0.0.0/8', FILTER_FLAG_NO_RES_RANGE],
        ];
    }

    /**
     * @return array
     */
    public function getClientOsDataProvider(): array
    {
        return [
            ['Linux', 'Mozilla/5.0 (Linux; Android 6.0; HTC One X10 Build/MRA58K; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/61.0.3163.98 Mobile Safari/537.36'],
            ['Mac', 'Mozilla/5.0 (iPhone; CPU iPhone OS 12_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/12.0 Mobile/15E148 Safari/604.1'],
            ['Windows', 'Mozilla/5.0 (Windows Phone 10.0; Android 4.2.1; Microsoft; Lumia 950) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/46.0.2486.0 Mobile Safari/537.36 Edge/13.1058'],
            ['Other', ''],
        ];
    }

    /**
     * @return array
     */
    public function getParamDataProvider(): array
    {
        return [
            [['param1', 'param2', 'param3'], null, ['param1', 'param2', 'param3'], null],
            ['param1', null, ['param1', 'param2', 'param3'], '0'],
            ['param1', null, ['key' => 'param1', 'param2', 'param3'], 'key'],
            ['val1', null, ['key' => ['key2' => 'val1', 'key3' => 'val2'], 'param2', 'param3'], 'key.key2'],
            ['DEFAULT', 'DEFAULT', ['key' => 'param1', 'param2', 'param3'], 'key.notaparam'],
        ];
    }

    /**
     * @return array
     */
    public function checkRequestSpecialPathDataProvider(): array
    {
        return [
            ['login'],
            ['logout'],
            ['update'],
        ];
    }

    /**
     * @inheritdoc
     */
    protected function _before(): void
    {
        parent::_before();

        $this->request = new Request([
            'cookieValidationKey' => 'lashdao8u09ud09u09231uoij098wqe',
        ]);
    }

    /**
     * @param string|null $name
     * @param mixed $defaultValue
     * @param array $params
     * @return mixed
     * @throws ReflectionException
     */
    private function _getParam(?string $name, mixed $defaultValue, array $params): mixed
    {
        return $this->invokeMethod($this->request, '_getParam', [$name, $defaultValue, $params]);
    }

    /**
     * @param string $token
     * @return mixed
     * @throws ReflectionException
     */
    private function _isCsrfValidForUser(string $token): mixed
    {
        return $this->invokeMethod($this->request, 'csrfTokenValidForCurrentUser', [$token]);
    }

    /**
     * @return mixed
     * @throws ReflectionException
     */
    private function _generateCsrfToken(): mixed
    {
        return $this->invokeMethod($this->request, 'generateCsrfToken');
    }

    /**
     *
     */
    private function _setMockUser()
    {
        Craft::$app->getUser()->setIdentity(
            Craft::$app->getUsers()->getUserById(1)
        );
    }
}
