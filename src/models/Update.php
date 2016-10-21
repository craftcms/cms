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
    public function init()
    {
        parent::init();

        if (isset($this->plugins)) {
            foreach ($this->plugins as $key => $value) {
                if (!$value instanceof PluginUpdate) {
                    $this->plugins[$key] = new PluginUpdate($value);
                }
            }
        }
    }

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
