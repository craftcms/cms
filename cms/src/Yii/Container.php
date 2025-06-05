<?php
/**
 * @link https://github.com/yii2tech
 * @copyright Copyright (c) 2019 Yii2tech
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace Craft\Cms\Yii;

use Illuminate\Container\Container as IlluminateContainer;
use Illuminate\Contracts\Container\Container as ContainerContract;

class Container extends \yii\di\Container
{
    private ?ContainerContract $container = null;

    public function getContainer(): ContainerContract
    {
        return $this->container ??= IlluminateContainer::getInstance();
    }

    /**
     * {@inheritdoc}
     */
    public function get($class, $params = [], $config = [])
    {
        if ($this->getContainer()->has($class)) {
            return $this->getContainer()->get($class);
        }

        return parent::get($class, $params, $config);
    }

    /**
     * {@inheritdoc}
     */
    public function has($class): bool
    {
        if ($this->getContainer()->has($class)) {
            return true;
        }

        return parent::has($class);
    }
}
