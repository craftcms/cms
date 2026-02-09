<?php

declare(strict_types=1);

namespace CraftCms\Cms\Component;

use CraftCms\Cms\Support\Typecast;
use Illuminate\Contracts\Support\Arrayable;
use RuntimeException;
use Yiisoft\Arrays\ArrayableInterface;
use Yiisoft\Arrays\ArrayableTrait;

abstract class Component implements Arrayable, ArrayableInterface
{
    use ArrayableTrait;

    public function __construct(array $config = [])
    {
        Typecast::properties(static::class, $config);

        foreach ($config as $key => $value) {
            $this->$key = $value;
        }
    }

    public function __get(string $name)
    {
        $getter = 'get'.$name;

        if (method_exists($this, $getter)) {
            return $this->$getter();
        }

        if (method_exists($this, 'set'.$name)) {
            throw new RuntimeException('Getting write-only property: '.static::class.'::'.$name);
        }

        throw new RuntimeException('Getting unknown property: '.static::class.'::'.$name);
    }

    public function __set(string $name, $value): void
    {
        $setter = 'set'.$name;

        if (method_exists($this, $setter)) {
            // set property
            $this->$setter($value);

            return;
        }

        if (method_exists($this, 'get'.$name)) {
            throw new RuntimeException('Setting read-only property: '.static::class.'::'.$name);
        }

        throw new RuntimeException('Setting unknown property: '.static::class.'::'.$name);
    }

    public function __isset(string $name): bool
    {
        $getter = 'get'.$name;

        if (method_exists($this, $getter)) {
            return $this->$getter() !== null;
        }

        return false;
    }

    public function __unset(string $name): void
    {
        $setter = 'set'.$name;

        if (method_exists($this, $setter)) {
            $this->$setter(null);

            return;
        }

        throw new RuntimeException('Unsetting an unknown or read-only property: '.static::class.'::'.$name);
    }
}
