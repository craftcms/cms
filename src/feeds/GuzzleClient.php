<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\feeds;

use Craft;
use GuzzleHttp\ClientInterface as GuzzleClientInterface;
use Zend\Feed\Reader\Http\ClientInterface as FeedReaderHttpClientInterface;
use Zend\Feed\Reader\Http\Psr7ResponseDecorator;

/**
 * PSR-7 Guzzle client for the Feeds service.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class GuzzleClient implements FeedReaderHttpClientInterface
{
    /**
     * @var GuzzleClientInterface|null
     */
    private $_client;

    /**
     * @param GuzzleClientInterface|null $client
     */
    public function __construct(GuzzleClientInterface $client = null)
    {
        $this->_client = $client ?: Craft::createGuzzleClient();
    }

    /**
     * {@inheritdoc}
     */
    public function get($uri)
    {
        return new Psr7ResponseDecorator(
            $this->_client->request('GET', $uri)
        );
    }
}
