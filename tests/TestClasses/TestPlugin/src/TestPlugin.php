<?php

declare(strict_types=1);

namespace CraftCms\Cms\Tests\TestClasses\TestPlugin\src;

use Closure;
use CraftCms\Cms\Dashboard\Contracts\WidgetInterface;
use CraftCms\Cms\Database\Migration;
use CraftCms\Cms\Database\Migrator;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Field\Contracts\FieldInterface;
use CraftCms\Cms\Plugin\Plugin;
use CraftCms\Cms\Utility\Utility;
use CraftCms\Cms\Validation\Contracts\Validatable;
use Illuminate\Console\Command;
use Override;

class TestPlugin extends Plugin
{
    public static bool $useSettings = true;

    public static bool $beforeSaveSettings = true;

    public static ?Closure $onAfterSaveSettings = null;

    public ?string $basePathOverride = null;

    /** @var array<int, mixed> */
    public array $customPermissions = [];

    public ?Migrator $customMigrator = null;

    public ?string $customSettingsHtml = null;

    public bool $didCallBeforeInstall = false;

    public bool $didCallAfterInstall = false;

    public bool $didCallBeforeUninstall = false;

    public bool $didCallAfterUninstall = false;

    #[Override]
    public ?string $packageName = 'craftcms/test-plugin';

    #[Override]
    public bool $hasCpSettings = true;

    #[Override]
    public bool $hasReadOnlyCpSettings = true;

    public function useBasePath(string $basePath): void
    {
        $this->basePathOverride = $basePath;
    }

    public function useMigrator(Migrator $migrator): void
    {
        $this->customMigrator = $migrator;
    }

    public function setPermissions(array $permissions): void
    {
        $this->customPermissions = $permissions;
    }

    public function setSettingsHtml(?string $settingsHtml): void
    {
        $this->customSettingsHtml = $settingsHtml;
    }

    /** @param array<int, class-string<Command>> $commands */
    public function setCommands(array $commands): void
    {
        $this->commands = $commands;
    }

    /** @param array<int, class-string<Element>> $elementTypes */
    public function setElementTypes(array $elementTypes): void
    {
        $this->elementTypes = $elementTypes;
    }

    /** @param array<int, class-string<FieldInterface>> $fieldTypes */
    public function setFieldTypes(array $fieldTypes): void
    {
        $this->fieldTypes = $fieldTypes;
    }

    /** @param array<string, class-string|array<int, class-string>> $events */
    public function setListeners(array $events): void
    {
        $this->events = $events;
    }

    /** @param array<int, class-string<Utility>> $utilities */
    public function setUtilities(array $utilities): void
    {
        $this->utilities = $utilities;
    }

    /** @param array<int, class-string<WidgetInterface>> $widgets */
    public function setWidgets(array $widgets): void
    {
        $this->widgets = $widgets;
    }

    /** @param array<string, string> $publishables */
    public function setPublishables(array $publishables): void
    {
        $this->publishables = $publishables;
    }

    /** @param array<string, mixed>|array<int, string> $vite */
    public function setVite(array $vite): void
    {
        $this->vite = $vite;
    }

    /** @param array<string, string>|array<int, string> $styles */
    public function setStyles(array $styles): void
    {
        $this->styles = $styles;
    }

    /** @param array<string, string>|array<int, string> $scripts */
    public function setScripts(array $scripts): void
    {
        $this->scripts = $scripts;
    }

    #[Override]
    public static function editions(): array
    {
        return [
            'standard',
            'pro',
        ];
    }

    #[Override]
    public function getBasePath(): string
    {
        return $this->basePathOverride ?? parent::getBasePath();
    }

    #[Override]
    public function getMigrator(): Migrator
    {
        return $this->customMigrator ?? parent::getMigrator();
    }

    #[Override]
    public function createInstallMigration(): ?object
    {
        $path = $this->getMigrationsPath().'/Install.php';

        if (! is_file($path)) {
            return null;
        }

        $migration = require $path;

        if ($migration instanceof Migration) {
            return $migration;
        }

        return parent::createInstallMigration();
    }

    #[Override]
    protected function getPermissions(): array
    {
        return $this->customPermissions;
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
    protected function settingsHtml(): ?string
    {
        return $this->customSettingsHtml ?? '<input id="settings-foo" name="foo" value="'.e($this->getSettings()?->foo).'">';
    }

    #[Override]
    protected function beforeInstall(): void
    {
        $this->didCallBeforeInstall = true;
    }

    #[Override]
    protected function afterInstall(): void
    {
        $this->didCallAfterInstall = true;
    }

    #[Override]
    protected function beforeUninstall(): void
    {
        $this->didCallBeforeUninstall = true;
    }

    #[Override]
    protected function afterUninstall(): void
    {
        $this->didCallAfterUninstall = true;
    }

    #[Override]
    public function beforeSaveSettings(): bool
    {
        return self::$beforeSaveSettings;
    }

    #[Override]
    public function afterSaveSettings(): void
    {
        if (self::$onAfterSaveSettings) {
            (self::$onAfterSaveSettings)();
        }
    }
}
