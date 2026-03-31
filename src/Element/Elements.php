<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element;

use craft\base\ElementInterface;
use CraftCms\Cms\Component\ComponentHelper;
use Illuminate\Container\Attributes\Singleton;

#[Singleton]
class Elements
{
    /**
     * Creates an element with a given config.
     *
     * @template T of ElementInterface
     *
     * @param  class-string<T>|array{type:class-string<T>}  $config  The element’s class name, or its config, with a `type` value
     * @return T The element
     */
    public function createElement(mixed $config): ElementInterface
    {
        if (is_string($config)) {
            $config = ['type' => $config];
        }

        return ComponentHelper::createComponent($config, ElementInterface::class);
    }
}
