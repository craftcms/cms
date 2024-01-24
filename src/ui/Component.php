<?php

namespace craft\ui;

use Craft;
use Illuminate\Support\Str;
use ReflectionClass;

class Component extends ViewComponent
{
    public function setUp(): void
    {
        $this->viewData([
            'handle' => $this->getHandle(),
        ]);
    }

    public function getHandle(): string
    {
        $reflect = new ReflectionClass($this);
        return Str::kebab($reflect->getShortName());
    }

    public static function make(): static
    {
        $static = Craft::createObject(static::class);
        // TODO: this should be $static->configure() once we're full laravel
        $static->setUp();

        return $static;
    }
}
