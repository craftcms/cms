<?php

/**
 * @link https://github.com/yii2tech
 *
 * @copyright Copyright (c) 2019 Yii2tech
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace CraftCms\Yii2Adapter\Log;

use yii\base\Component;

class Logger extends \yii\log\Logger
{
    /**
     * {@inheritdoc}
     */
    public function init(): void
    {
        Component::init(); // skip parent init, avoiding `register_shutdown_function()` call.
    }
}
