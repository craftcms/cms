<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\base;

use Craft;
use Craft\Services\Config;
use GuzzleHttp\Client;

/**
 * Used for creating a PSR-7 Guzzle client with system-wide and request specific
 * config options merged in.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class GuzzleFactory
{
    /**
     * Returns a PSR-7 Guzzle client created with config options merged.
     *
     * @param array $config Any request specific config options to merge in.
     *
     * @return Client
     */
    public static function create(array $config = [])
    {
        // Set the Craft header by default.
        $defaultConfig = [
            'headers' => [
                'User-Agent' => 'Craft/'.Craft::$app->version.' '.\GuzzleHttp\default_user_agent()
            ],
        ];

        // Grab the config from craft/config/guzzle.php that is used on every Guzzle request.
        $guzzleConfig = Craft::$app->getConfig()->getConfigSettings(Config::CATEGORY_GUZZLE);

        // Merge default into guzzle config.
        $guzzleConfig = array_replace_recursive($guzzleConfig, $defaultConfig);

        // Maybe they want to set some config options specifically for this request.
        $guzzleConfig = array_replace_recursive($guzzleConfig, $config);

        return new Client([
            $guzzleConfig
        ]);
    }
}
