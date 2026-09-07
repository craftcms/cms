<?php

declare(strict_types=1);

namespace CraftCms\Cms\Tests\TestClasses\TestPlugin\src;

use CraftCms\Cms\Plugin\Plugin;
use Override;

class FluentTestPlugin extends Plugin
{
    public array $registeredSettings = [];

    public array $bootedSettings = [];

    #[Override]
    public static function config(): TestPluginSettings
    {
        return parent::config();
    }

    #[Override]
    protected static function createSettings(): TestPluginSettings
    {
        return TestPluginSettings::create();
    }

    #[Override]
    public function register(): void
    {
        $this->registeredSettings = $this->getSettings()->validationData();
    }

    public function boot(): void
    {
        $this->bootedSettings = $this->getSettings()->validationData();
    }
}
