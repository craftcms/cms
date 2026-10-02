<?php

declare(strict_types=1);

namespace CraftCms\Cms\Tests\TestClasses\TestPlugin\src;

use CraftCms\Cms\Plugin\PluginSettings;
use Override;

class TestPluginSettings extends PluginSettings
{
    public ?string $foo = null;

    public ?string $bar = null;

    public function foo(?string $value): static
    {
        $this->foo = $value;

        return $this;
    }

    public function bar(?string $value): static
    {
        $this->bar = $value;

        return $this;
    }

    #[Override]
    public function getRules(): array
    {
        return [
            'foo' => 'required',
        ];
    }
}
