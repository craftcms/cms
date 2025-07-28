<?php

/**
 * @link https://github.com/yii2tech
 *
 * @copyright Copyright (c) 2019 Yii2tech
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace CraftCms\Yii2Adapter;

class Container extends \yii\di\Container
{
    /**
     * {@inheritdoc}
     */
    public function get($class, $params = [], $config = [])
    {
        if (app()->has($class)) {
            return app()->get($class);
        }

        return parent::get($class, $params, $config);
    }

    /**
     * {@inheritdoc}
     */
    public function has($class): bool
    {
        if (app()->has($class)) {
            return true;
        }

        return parent::has($class);
    }
}
