<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\config;

use yii\base\Object;
use yii\caching\MemCache;

/**
 * MemCache Config class
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class MemCacheConfig extends Object
{
    // Properties
    // =========================================================================

    /**
     * @var array An array of memcached servers to use.
     * @see MemCache::setServers()
     */
    public $servers = [
        [
            // A memcached server hostname or IP address.
            'host' => 'localhost',
            // Whether or not to use a persistent connection.
            'persistent' => true,
            // The memcached server port.
            'port' => 11211,
            // How often a failed server will be retried (in seconds).
            'retryInterval' => 15,
            // If the server should be flagged as online upon a failure.
            'status' => true,
            // The value in seconds which will be used for connecting to the server.
            'timeout' => 15,
            // Probability of using this server among all servers.
            'weight' => 1,
        ],
    ];
}
