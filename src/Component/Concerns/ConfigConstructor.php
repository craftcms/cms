<?php

declare(strict_types=1);

namespace CraftCms\Cms\Component\Concerns;

use CraftCms\Cms\Support\Typecast;

trait ConfigConstructor
{
    public function __construct(array $config = [])
    {
        Typecast::properties(static::class, $config);

        foreach ($config as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            } elseif (method_exists($this, 'set'.ucfirst((string) $key))) {
                $this->{'set'.ucfirst((string) $key)}($value);
            }
        }
    }
}
