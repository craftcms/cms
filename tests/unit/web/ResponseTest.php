<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\web;

use Codeception\Test\Unit;
use craft\helpers\DateTimeHelper;
use craft\test\TestCase;
use craft\web\Response;

/**
 * Unit tests for Response
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2
 */
class ResponseTest extends TestCase
{
    /**
     * @var Response
     */
    public Response $response;

    /**
     * @dataProvider getContentTypeDataProvider
     * @param string|null $expected
     * @param string|null $format
     * @param string|null $contentType
     */
    public function testGetContentType(?string $expected, ?string $format = null, ?string $contentType = null): void
    {
        $this->response->format = $format ?? Response::FORMAT_RAW;

        if ($contentType !== null) {
            $this->response->getHeaders()->set('content-type', $contentType);
        }

        self::assertSame($expected, $this->response->getContentType());
    }

    /**
     *
     */
    public function testSetCacheHeaders(): void
    {
        DateTimeHelper::pause();
        $this->response->setCacheHeaders();
        $headers = $this->response->getHeaders();
        $cacheTime = 31536000; // 1 year
        self::assertSame('cache', $headers->get('Pragma'));
        self::assertSame('cache', $headers->get('Pragma'));
        self::assertSame('public, max-age=31536000', $headers->get('Cache-Control'));
        $expectedExpires = DateTimeHelper::currentTimeStamp() + $cacheTime;
        self::assertSame(gmdate('D, d M Y H:i:s', $expectedExpires) . ' GMT', $headers->get('Expires'));
        DateTimeHelper::resume();
    }

    /**
     *
     */
    public function testSetLastModifiedHeader(): void
    {
        // Use the current file
        $path = dirname(__DIR__) . '/web/ResponseTest.php';
        $modifiedTime = filemtime($path);

        $this->response->setLastModifiedHeader($path);

        self::assertSame(gmdate('D, d M Y H:i:s', $modifiedTime) . ' GMT', $this->response->getHeaders()->get('Last-Modified'));
    }

    /**
     * @param string $expected
     * @param mixed $url
     * @dataProvider testRedirectDataProvider
     */
    public function testRedirect(string $expected, mixed $url): void
    {
        self::assertEquals($expected, $this->response->redirect($url)->headers->get('location'));
    }

    /**
     * @return array
     */
    public static function getContentTypeDataProvider(): array
    {
        return [
            ['text/html', Response::FORMAT_HTML],
            ['application/xml', Response::FORMAT_XML],
            ['application/json', Response::FORMAT_JSON],
            ['application/javascript', Response::FORMAT_JSONP],
            ['application/javascript', null, 'application/javascript'],
            ['not-a-header', null, 'not-a-header'],
            ['application/javascript', null, 'application/javascript;'],
        ];
    }

    /**
     * @inheritdoc
     */
    protected function _before(): void
    {
        parent::_before();
        $this->response = new Response();
    }

    /**
     * @return array
     */
    public static function testRedirectDataProvider(): array
    {
        return [
            ['https://test.craftcms.test/', ''],
            ['http://some-external-domain.com', 'http://some-external-domain.com'],
            ['https://test.craftcms.test:80/', '/'],
            ['https://test.craftcms.test:80/something-relative', '/something-relative'],
            ['https://test.craftcms.test:80/actions/foo/bar', ['foo/bar']],
            ['https://test.craftcms.test:80/actions/foo/bar?id=3', ['foo/bar', 'id' => 3]],
        ];
    }
}
