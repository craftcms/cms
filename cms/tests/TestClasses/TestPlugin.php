<?php

namespace CraftCms\Cms\Tests\TestClasses;

use Closure;
use CraftCms\Cms\Component\Contracts\ValidatableComponentInterface;
use CraftCms\Cms\Plugin\Plugin;

final class TestPlugin extends Plugin
{
    public static bool $useSettings = true;

    public static bool $beforeSaveSettings = true;

    public static ?Closure $onAfterSaveSettings = null;

    public ?string $packageName = 'craftcms/test-plugin';

    public static function editions(): array
    {
        return [
            'standard',
            'pro',
        ];
    }

    protected function createSettingsModel(): ?ValidatableComponentInterface
    {
        if (! self::$useSettings) {
            return null;
        }

        return new TestPluginSettings;
    }

    public function beforeSaveSettings(): bool
    {
        return self::$beforeSaveSettings;
    }

    public function afterSaveSettings(): void
    {
        if ($closure = self::$onAfterSaveSettings) {
            $closure();
        }
    }
}
