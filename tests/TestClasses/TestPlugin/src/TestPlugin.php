<?php

declare(strict_types=1);

namespace CraftCms\Cms\Tests\TestClasses\TestPlugin\src;

use Closure;
use CraftCms\Cms\Dashboard\Contracts\WidgetInterface;
use CraftCms\Cms\Database\Migration;
use CraftCms\Cms\Database\Migrator;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Field\Contracts\FieldInterface;
use CraftCms\Cms\Field\LinkTypes\BaseLinkType;
use CraftCms\Cms\Filesystem\Contracts\FsInterface;
use CraftCms\Cms\Form\Controls\Text;
use CraftCms\Cms\Form\Form;
use CraftCms\Cms\Form\FormContext;
use CraftCms\Cms\Form\FormControlTypes;
use CraftCms\Cms\Form\FormNodeTypes;
use CraftCms\Cms\Form\Nodes\Field;
use CraftCms\Cms\Gql\Contracts\SingularTypeInterface;
use CraftCms\Cms\Gql\Directives\Directive;
use CraftCms\Cms\Gql\Mutations\Mutation;
use CraftCms\Cms\Gql\Queries\Query;
use CraftCms\Cms\Plugin\Contracts\PluginInterface;
use CraftCms\Cms\Plugin\Plugin;
use CraftCms\Cms\Tests\TestClasses\TestPlugin\src\Form\Controls\Slug;
use CraftCms\Cms\Tests\TestClasses\TestPlugin\src\Form\Nodes\Notice;
use CraftCms\Cms\Utility\Utility;
use CraftCms\Cms\Validation\Contracts\Validatable;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Override;

class TestPlugin extends Plugin
{
    public static bool $useSettings = true;

    public static bool $useSettingsForm = true;

    public static bool $beforeSaveSettings = true;

    public static ?Closure $onAfterSaveSettings = null;

    /** @var class-string<Request> */
    public static string $settingsRequestClass = Request::class;

    /** @var array<string, string> */
    public static array $customPublishables = [];

    /** @var array<string, string> */
    public static array $customStyles = [];

    /** @var array<string, string> */
    public static array $customScripts = [];

    public ?string $basePathOverride = null;

    /** @var array<int, mixed> */
    public array $customPermissions = [];

    public ?Migrator $customMigrator = null;

    public ?Closure $customNativeFields = null;

    /** @var array<string, array|Closure> */
    public array $customCacheOptions = [];

    /** @var array<string, string|Closure> */
    public array $customCacheTags = [];

    /** @var array<string, Closure> */
    public array $customSystemMessages = [];

    public bool $didCallBeforeInstall = false;

    public bool $didCallAfterInstall = false;

    public bool $didCallBeforeUninstall = false;

    public bool $didCallAfterUninstall = false;

    public function registerFormTypes(FormNodeTypes $nodeTypes, FormControlTypes $controlTypes): void
    {
        $nodeTypes->register(Notice::class);
        $controlTypes->register(Slug::class);
    }

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

    /** @param array<int, class-string<FsInterface>> $filesystemTypes */
    public function setFilesystemTypes(array $filesystemTypes): void
    {
        $this->filesystemTypes = $filesystemTypes;
    }

    /** @param array<int, class-string<SingularTypeInterface>> $gqlTypes */
    public function setGqlTypes(array $gqlTypes): void
    {
        $this->gqlTypes = $gqlTypes;
    }

    /** @param array<int, class-string<Query>> $gqlQueries */
    public function setGqlQueries(array $gqlQueries): void
    {
        $this->gqlQueries = $gqlQueries;
    }

    /** @param array<int, class-string<Mutation>> $gqlMutations */
    public function setGqlMutations(array $gqlMutations): void
    {
        $this->gqlMutations = $gqlMutations;
    }

    /** @param array<int, class-string<Directive>> $gqlDirectives */
    public function setGqlDirectives(array $gqlDirectives): void
    {
        $this->gqlDirectives = $gqlDirectives;
    }

    /** @param array<int, class-string<BaseLinkType>> $linkTypes */
    public function setLinkTypes(array $linkTypes): void
    {
        $this->linkTypes = $linkTypes;
    }

    public function setNativeFields(Closure $nativeFields): void
    {
        $this->customNativeFields = $nativeFields;
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

    /** @param array<string, array|Closure> $cacheOptions */
    public function setCacheOptions(array $cacheOptions): void
    {
        $this->customCacheOptions = $cacheOptions;
    }

    /** @param array<string, string|Closure> $cacheTags */
    public function setCacheTags(array $cacheTags): void
    {
        $this->customCacheTags = $cacheTags;
    }

    /** @param array<string, Closure> $systemMessages */
    public function setSystemMessages(array $systemMessages): void
    {
        $this->customSystemMessages = $systemMessages;
    }

    /** @param array<string, string|string[]> $siteTemplateRoots */
    public function setSiteTemplateRoots(array $siteTemplateRoots): void
    {
        $this->siteTemplateRoots = $siteTemplateRoots;
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
    public static function create(array $config): PluginInterface
    {
        if (self::$customPublishables !== []) {
            $config['publishables'] = self::$customPublishables;
        }

        if (self::$customStyles !== []) {
            $config['styles'] = self::$customStyles;
        }

        if (self::$customScripts !== []) {
            $config['scripts'] = self::$customScripts;
        }

        return parent::create($config);
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
    protected function getNativeFields(): ?Closure
    {
        return $this->customNativeFields;
    }

    #[Override]
    protected function getCacheOptions(): array
    {
        return $this->customCacheOptions;
    }

    #[Override]
    protected function getCacheTags(): array
    {
        return $this->customCacheTags;
    }

    #[Override]
    protected function getSystemMessages(): array
    {
        return $this->customSystemMessages;
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
    public function settingsForm(FormContext $context = new FormContext): ?Form
    {
        if (! self::$useSettingsForm) {
            return null;
        }

        return Form::make([
            Field::make('Foo', Text::make('foo')->reactive()),
        ])->when($this->getSettings()?->foo === 'show-bar', fn (Form $form) => $form->add(
            Field::make('Bar', Text::make('bar')),
        ));
    }

    #[Override]
    public function getSettingsRequestClass(): string
    {
        return self::$settingsRequestClass;
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
