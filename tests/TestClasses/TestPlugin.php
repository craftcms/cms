<?php

declare(strict_types=1);

namespace CraftCms\Cms\Tests\TestClasses;

use Closure;
use CraftCms\Cms\Component\Validation\Contracts\Validatable;
use CraftCms\Cms\Plugin\Plugin;
use Override;

final class TestPlugin extends Plugin
{
    public static bool $useSettings = true;

    public static bool $beforeSaveSettings = true;

    public static ?Closure $onAfterSaveSettings = null;

    public ?string $packageName = 'craftcms/test-plugin';

    #[Override]
    public static function editions(): array
    {
        return [
            'standard',
            'pro',
        ];
    }

    #[Override]
    protected function createSettingsModel(): ?Validatable
    {
        if (! self::$useSettings) {
            return null;
        }

        return new TestPluginSettings;
    }

    #[Override]
    public function beforeSaveSettings(): bool
    {
        return self::$beforeSaveSettings;
    }

    #[Override]
    public function afterSaveSettings(): void
    {
        if ($closure = self::$onAfterSaveSettings) {
            $closure();
        }
    }
}
