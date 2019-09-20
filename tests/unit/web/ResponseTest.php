<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\web;

use Codeception\Test\Unit;
use craft\web\Response;

/**
 * Unit tests for Response
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2
 */
class ResponseTest extends Unit
{
    // Public Properties
    // =========================================================================

    /**
     * @var Response
     */
    public $response;

    // Public Methods
    // =========================================================================

    // Tests
    // =========================================================================

    /**
     * @dataProvider getContentTypeDataProvider
     *
     * @param $result
     * @param $format
     * @param null $header
     */
    public function testGetContentType($result, $format, $header = null)
    {
        $this->response->format = $format;

        if ($header !== null) {
            $this->response->headers->set('content-type', $header);
        }

        $type = $this->response->getContentType();
        $this->assertSame($result, $type);
    }

    /**
     *
     */
    public function testSetCacheHeaders()
    {
        $this->response->setCacheHeaders();
        $headers = $this->response->getHeaders();

        $cacheTime = 31536000; // 1 year

        $this->assertSame('cache', $headers->get('Pragma'));
        $this->assertSame('cache', $headers->get('Pragma'));
        $this->assertSame('max-age=31536000', $headers->get('Cache-Control'));
        $this->assertSame(gmdate('D, d M Y H:i:s', time() + $cacheTime) . ' GMT', $headers->get('Expires'));
    }

    /**
     *
     */
    public function testSetLastModifiedHeader()
    {
        // Use the current file
        $path = dirname(__DIR__) . '/web/ResponseTest.php';
        $modifiedTime = filemtime($path);

        $this->response->setLastModifiedHeader($path);

        $this->assertSame(gmdate('D, d M Y H:i:s', $modifiedTime) . ' GMT', $this->response->getHeaders()->get('Last-Modified'));
    }

    // Data Providers
    // =========================================================================

    /**
     * @return array
     */
    public function getContentTypeDataProvider(): array
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

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function _before()
    {
        parent::_before();
        $this->response = new Response();
    }
}
