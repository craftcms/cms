<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */


namespace craftunit\web;


use Codeception\Test\Unit;
use craft\web\Response;

/**
 * Unit tests for ResponseTest
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.0
 */
class ResponseTest extends Unit
{
    /**
     * @var Response
     */
    public $response;

    public function _before()
    {
        parent::_before();
        
        $this->response = new Response();
    }

    /**
     * @param $result
     * @param $format
     * @param null $header
     * @dataProvider getContentTypeData
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
    public function getContentTypeData()
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

    public function testSetCacheHeaders()
    {
        $this->response->setCacheHeaders();
        $headers = $this->response->getHeaders();

        $cacheTime = 31536000; // 1 year

        $this->assertSame('cache', $headers->get('Pragma'));
        $this->assertSame('cache', $headers->get('Pragma'));
        $this->assertSame('max-age=31536000', $headers->get('Cache-Control'));
        $this->assertSame(gmdate('D, d M Y H:i:s', time() + $cacheTime).' GMT', $headers->get('Expires'));
    }

    public function testSetLastModifiedHeader()
    {
        // Use the current file
        $path = dirname(__DIR__).'/web/ResponseTest.php';
        $modifiedTime = filemtime($path);

        $this->response->setLastModifiedHeader($path);

        $this->assertSame(gmdate('D, d M Y H:i:s', $modifiedTime) . ' GMT', $this->response->getHeaders()->get('Last-Modified'));
    }
}