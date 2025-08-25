<?php

namespace CraftCms\Cms\Tests\TestClasses;

use CraftCms\Cms\Plugin\PluginSettings;

final class TestPluginSettings extends PluginSettings
{
    public ?string $foo = null;

    public static function getRules(): array
    {
        return [
            'foo' => 'required',
        ];
    }
}
