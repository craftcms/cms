<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element;

use craft\base\ElementInterface;
use CraftCms\Cms\Component\ComponentHelper;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use Illuminate\Container\Attributes\Singleton;
use InvalidArgumentException;

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

    /**
     * Creates an element query for a given element type.
     *
     * @param  class-string<ElementInterface>  $elementType  The element class
     * @return ElementQueryInterface The element query
     *
     * @throws InvalidArgumentException if $elementType is not a valid element
     */
    public function createElementQuery(string $elementType): ElementQueryInterface
    {
        if (! is_subclass_of($elementType, ElementInterface::class)) {
            throw new InvalidArgumentException("$elementType is not a valid element.");
        }

        return $elementType::find();
    }
}
