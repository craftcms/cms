<?php

namespace CraftCms\Cms\Plugin;

use Closure;
use Craft;
use craft\base\Element;
use craft\base\FieldInterface;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterTemplateRootsEvent;
use craft\i18n\PhpMessageSource;
use craft\services\Elements;
use craft\services\Fields;
use craft\web\View;
use CraftCms\Cms\Dashboard\Contracts\WidgetInterface;
use CraftCms\Cms\Dashboard\Events\RegisterWidgetTypes;
use CraftCms\Cms\Plugin\Contracts\PluginInterface;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Utility\Events\RegisterUtilities;
use CraftCms\Cms\Utility\Utility;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

/**
 * The provider Craft CMS Plugins must extend
 */
abstract class PluginServiceProvider extends ServiceProvider
{
    /**
     * Array of event class => Listener class.
     *
     * @var array<class-string, class-string|class-string[]>
     */
    protected array $events = [];

    /**
     * Array of widget classes to register.
     *
     * @var class-string<WidgetInterface>[]
     */
    protected array $widgets = [];

    /**
     * Array of utility classes to register.
     *
     * @var class-string<Utility>[]
     */
    protected array $utilities = [];

    /**
     * Array of element types to register.
     *
     * @var class-string<Element>[]
     */
    protected array $elementTypes = [];

    /**
     * Array of field types to register.
     *
     * @var class-string<FieldInterface>[]
     */
    protected array $fieldTypes = [];

    /**
     * A list of console command classes to register.
     *
     * @var list<class-string<Command>>
     */
    protected array $commands = [];

    /**
     * @var array - URLs of Vite entry points
     */
    protected array $vite = [];

    /**
     * Map of path on disk to name in the public directory. The file will be published
     * as `vendor/{pluginHandle}/{value}`.
     *
     * @var array<string, string>
     */
    protected array $publishables = [];

    private Plugins $plugins;

    private ?PluginInterface $plugin = null;

    /**
     * @internal
     */
    public function boot(Plugins $plugins): void
    {
        $this->plugins = $plugins;

        $handle = $this->plugins->getPluginHandleByClass(static::class);

        if (! $handle) {
            return;
        }

        if (! $this->plugins->isPluginInstalled($handle)) {
            return;
        }

        if (! $this->plugins->isPluginEnabled($handle)) {
            return;
        }

        $this
            ->bootViews()
            ->bootI18n()
            ->bootEvents()
            ->bootWidgets()
            ->bootUtilities()
            ->bootElementTypes()
            ->bootFieldTypes()
            ->bootCommands()
            ->bootVite()
            ->bootPublish()
            ->bootRoutes()
            ->bootPlugin();
    }

    public function bootViews(): self
    {
        // Base template directory
        \craft\base\Event::on(View::class, View::EVENT_REGISTER_CP_TEMPLATE_ROOTS, function (RegisterTemplateRootsEvent $e) {
            $basePath = $this->getPlugin()->getBasePath();
            $handle = $this->getPlugin()->handle;

            /**
             * Get the first matching directory for views or templates.
             */
            $baseDir = match (true) {
                // Laravel Convention
                is_dir($baseDir = dirname($basePath).'/resources/views') => $baseDir,
                // Laravel Convention for resources, Twig convention for templates
                is_dir($baseDir = dirname($basePath).'/resources/templates') => $baseDir,
                // Craft 5 and earlier
                is_dir($baseDir = $basePath.'/templates') => $baseDir,
                default => false,
            };

            if ($baseDir) {
                $e->roots[$handle] = $baseDir;
            }
        });

        return $this;
    }

    public function bootI18n(): self
    {
        // Translation category
        $i18n = Craft::$app->getI18n();
        $plugin = $this->getPlugin();
        $plugin->t9nCategory ??= $plugin->handle;

        $basePath = $plugin->getBasePath();
        $translationsPath = match (true) {
            // Laravel Convention - /lang
            is_dir($baseDir = dirname($basePath).'/lang') => $baseDir,
            // Craft 5 and earlier - src/translations
            default => $basePath.'/translations',
        };

        /** @noinspection UnSafeIsSetOverArrayInspection */
        if (! isset($i18n->translations[$plugin->t9nCategory]) && ! isset($i18n->translations[$plugin->t9nCategory.'*'])) {
            $i18n->translations[$plugin->t9nCategory] = [
                'class' => PhpMessageSource::class,
                'sourceLanguage' => $plugin->sourceLanguage,
                'basePath' => $translationsPath,
                'forceTranslation' => true,
                'allowOverrides' => true,
            ];
        }

        $this->loadTranslationsFrom($translationsPath, $plugin->t9nCategory);

        return $this;
    }

    public function bootEvents(): self
    {
        foreach ($this->events as $event => $listeners) {
            foreach (Arr::wrap($listeners) as $listener) {
                Event::listen($event, $listener);
            }
        }

        return $this;
    }

    public function bootWidgets(): self
    {
        if (! $this->widgets) {
            return $this;
        }

        Event::listen(RegisterWidgetTypes::class, function (RegisterWidgetTypes $event) {
            $event->types->push(...$this->widgets);
        });

        return $this;
    }

    public function bootUtilities(): self
    {
        if (! $this->utilities) {
            return $this;
        }

        Event::listen(RegisterUtilities::class, function (RegisterUtilities $event) {
            $event->types->push(...$this->utilities);
        });

        return $this;
    }

    public function bootElementTypes(): self
    {
        if (! $this->elementTypes) {
            return $this;
        }

        /** @todo: Laravelize */
        \craft\base\Event::on(
            Elements::class,
            Elements::EVENT_REGISTER_ELEMENT_TYPES,
            function (RegisterComponentTypesEvent $event) {
                array_push($event->types, ...$this->elementTypes);
            }
        );

        return $this;
    }

    public function bootFieldTypes(): self
    {
        if (! $this->fieldTypes) {
            return $this;
        }

        /** @todo: Laravelize */
        \craft\base\Event::on(
            Fields::class,
            Fields::EVENT_REGISTER_FIELD_TYPES,
            function (RegisterComponentTypesEvent $event) {
                array_push($event->types, ...$this->fieldTypes);
            }
        );

        return $this;
    }

    public function bootCommands(): self
    {
        $this->commands($this->commands);

        return $this;
    }

    public function bootVite(): self
    {
        if (! $this->vite) {
            return $this;
        }

        $config = $this->vite;
        $name = $this->getPlugin()->packageName;
        $directory = dirname($this->getPlugin()->getBasePath());

        if (! Arr::isAssoc($config)) {
            $config = ['input' => $config];
        }

        $publicDirectory = $config['publicDirectory'] ?? 'public';
        $buildDirectory = $config['buildDirectory'] ?? 'build';
        $hotFile = $config['hotFile'] ?? "{$directory}{$publicDirectory}/hot";
        $input = $config['input'];

        $publishSource = "{$directory}/{$publicDirectory}/{$buildDirectory}/";
        $publishTarget = public_path("vendor/{$name}/{$buildDirectory}/");

        $this->publishes([
            $publishSource => $publishTarget,
        ], $this->getPlugin()->handle);

        $this->plugins->addViteConfig($name, [
            'hotFile' => $hotFile,
            'buildDirectory' => "vendor/{$name}/{$buildDirectory}",
            'input' => $input,
        ]);

        return $this;
    }

    public function bootPublish(): self
    {
        $handle = $this->getPlugin()->handle;

        $publishes = Collection::make($this->publishables)
            ->map(fn (string $to) => public_path("vendor/{$handle}/{$to}"));

        if ($publishes->isNotEmpty()) {
            $this->publishes($publishes->all(), $handle);
        }

        return $this;
    }

    public function bootRoutes(): self
    {
        $directory = dirname($this->getPlugin()->getBasePath());

        foreach (['web', 'cp'] as $type) {
            if (! $this->app['files']->exists($path = "$directory/routes/$type.php")) {
                continue;
            }

            match ($type) {
                'web' => $this->registerWebRoutes($path),
                'cp' => $this->registerCpRoutes($path),
            };
        }

        return $this;
    }

    protected function registerWebRoutes(string|Closure $routes): void
    {
        $this->app['router']->middleware(['craft', 'craft.web'])->group($routes);
    }

    protected function registerCpRoutes(string|Closure $routes): void
    {
        $this->app['router']->middleware(['craft', 'craft.cp'])->group($routes);
    }

    public function bootPlugin(): void {}

    protected function getPlugin(): PluginInterface
    {
        if ($this->plugin) {
            return $this->plugin;
        }

        $handle = $this->plugins->getPluginHandleByClass(static::class);

        return $this->plugin = $this->plugins->getPlugin($handle);
    }
}
