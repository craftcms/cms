<?php

declare(strict_types=1);

namespace CraftCms\Cms\Tests\TestClasses\TestPlugin\src;

use CraftCms\Cms\Plugin\PluginSettings;
use Override;

class TestPluginSettings extends PluginSettings
{
    public ?string $foo = null;

    #[Override]
    public function getRules(): array
    {
        return [
            'foo' => 'required',
        ];
    }
}
