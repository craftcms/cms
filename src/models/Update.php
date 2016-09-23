<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\models;

use craft\app\base\Model;

/**
 * Stores all of the available update info.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class Update extends Model
{
    // Static
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function populateModel($model, $config)
    {
        if (isset($config['plugins'])) {
            foreach ($config['plugins'] as $key => $value) {
                if (!$value instanceof PluginUpdate) {
                    $config['plugins'][$key] = PluginUpdate::create($value);
                }
            }
        }

        parent::populateModel($model, $config);
    }

    // Properties
    // =========================================================================

    /**
     * @var AppUpdate App
     */
    public $app;

    /**
     * @var PluginUpdate[] Plugins
     */
    public $plugins = [];

    /**
     * @var array Errors
     */
    public $errors;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['app', 'plugins', 'errors'], 'safe', 'on' => 'search'],
        ];
    }
}
