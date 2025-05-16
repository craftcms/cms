<?php
/**
 * @link https://github.com/yii2tech
 * @copyright Copyright (c) 2019 Yii2tech
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace Craft\Cms\Yii;

use Illuminate\Contracts\Container\Container as ContainerContract;

class Container extends \yii\di\Container
{
    private ?ContainerContract $laravelContainer = null;

    public function getLaravelContainer(): ContainerContract
    {
        $this->laravelContainer ??= $this->defaultLaravelContainer();

        return $this->laravelContainer;
    }

    protected function defaultLaravelContainer(): ContainerContract
    {
        return \Illuminate\Container\Container::getInstance();
    }

    /**
     * {@inheritdoc}
     */
    public function get($class, $params = [], $config = [])
    {
        if ($this->getLaravelContainer()->has($class)) {
            return $this->getLaravelContainer()->get($class);
        }

        return parent::get($class, $params, $config);
    }

    /**
     * {@inheritdoc}
     */
    public function has($class): bool
    {
        if ($this->getLaravelContainer()->has($class)) {
            return true;
        }

        return parent::has($class);
    }
}
