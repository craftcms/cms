<?php

declare(strict_types=1);

namespace CraftCms\Cms\Twig\Variables;

class Facade
{
    public function __construct(
        /** @var class-string<\Illuminate\Support\Facades\Facade> */
        private readonly string $facade,
    ) {}

    /**
     * Call the method on the facade.
     */
    /** @param list<mixed> $arguments */
    public function __call(string $method, array $arguments): mixed
    {
        $facade = $this->facade;
        $instance = $facade::getFacadeRoot();

        if (! $instance) {
            return forward_static_call_array([$facade, $method], $arguments);
        }

        if (method_exists($instance, $method)) {
            return forward_static_call_array([$facade, $method], $arguments);
        }

        $getter = 'get'.ucfirst($method);

        if ($arguments === [] && method_exists($instance, $getter)) {
            return forward_static_call_array([$facade, $getter], []);
        }

        return forward_static_call_array([$facade, $method], $arguments);
    }

    public function __get(string $name): mixed
    {
        return $this->__call($name, []);
    }

    public function __isset(string $name): bool
    {
        $facade = $this->facade;
        $instance = $facade::getFacadeRoot();

        return $instance && (method_exists($instance, $name) || method_exists($instance, 'get'.ucfirst($name)));
    }
}
