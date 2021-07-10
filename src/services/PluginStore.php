<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use yii\base\Component;

/**
 * Plugin Store service.
 * An instance of the Plugin Store service is globally accessible in Craft via [[\craft\base\ApplicationTrait::getPluginStore()|`Craft::$app->pluginStore`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class PluginStore extends Component
{
    /**
     * @var string API endpoint
     */
    public $craftApiEndpoint = 'https://api.craftcms.com/v1';

    /**
     * @var string Dev server manifest path
     */
    public $devServerManifestPath = 'https://localhost:8082/';

    /**
     * @var string Dev server public path
     */
    public $devServerPublicPath = 'https://localhost:8082/';

    /**
     * @var bool Enable dev server
     */
    public $useDevServer = false;
}
