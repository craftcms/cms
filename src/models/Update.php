<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\models;

use craft\base\Model;

/**
 * Stores all of the available update info.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class Update extends Model
{
    // Properties
    // =========================================================================

    /**
     * @var AppUpdate|null App
     */
    public $app;

    /**
     * @var PluginUpdate[] Plugins
     */
    public $plugins = [];

    /**
     * @var array|null Response errors
     */
    public $responseErrors;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        if ($this->plugins !== null) {
            foreach ($this->plugins as $packageName => $pluginUpdate) {
                if (!$pluginUpdate instanceof PluginUpdate) {
                    $this->plugins[$packageName] = new PluginUpdate($pluginUpdate);
                }
            }
        }
    }
}
