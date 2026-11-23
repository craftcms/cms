<?php

declare(strict_types=1);

namespace CraftCms\Cms\Config;

use CraftCms\Aliases\Aliases;
use CraftCms\Cms\Plugin\PluginSettings;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Attributes\EnvName;
use CraftCms\Cms\Support\Env;
use CraftCms\Cms\Support\Facades\Deprecator;
use CraftCms\Cms\Support\HtmlSanitizer\HtmlSanitizerManager;
use CraftCms\Cms\Support\Typecast;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;
use Override;
use ReflectionProperty;

/**
 * @since 6.0.0
 */
class ConfigServiceProvider extends ServiceProvider
{
    /** @var list<string> */
    private array $configFiles = [
        'general',
        'redirects',
        'routes',
        'twig-sandbox',
    ];

    #[Override]
    public function register(): void
    {
        Env::extend(fn () => ConstAdapter::class, 'CraftConstAdapter');

        $this->loadEnvironmentVariablesWhenConfigIsCached();

        if (! $this->app->bound('config')) {
            return;
        }

        $this->normalizePluginSettings($this->app->make(ConfigRepository::class));

        $this->app->singleton(GeneralConfig::class, function () {
            $repository = $this->app->make(ConfigRepository::class);

            return $this->loadGeneralConfig($repository);
        });

        collect($this->configFiles)->each(function (string $file) {
            if ($file === 'general') {
                return;
            }

            if ($file === 'twig-sandbox') {
                $this->mergeTwigSandboxConfig();

                return;
            }

            $this->mergeConfigFrom(__DIR__."/../../config/$file.php", "craft.$file");
        });
    }

    /**
     * Extends Craft’s default sandbox allowlists with the project’s, rather than letting a
     * project list replace a default list.
     */
    private function mergeTwigSandboxConfig(): void
    {
        if ($this->app->configurationIsCached()) {
            return;
        }

        $repository = $this->app->make(ConfigRepository::class);

        $repository->set('craft.twig-sandbox', Arr::merge(
            require __DIR__.'/../../config/twig-sandbox.php',
            $repository->get('craft.twig-sandbox', []),
        ));
    }

    private function normalizePluginSettings(ConfigRepository $repository): void
    {
        $config = $repository->get('craft', []);

        if (! is_array($config)) {
            throw new InvalidArgumentException('Configuration [craft] must be an array; got '.get_debug_type($config).'.');
        }

        $changed = false;

        foreach ($config as $handle => $value) {
            if ($value instanceof PluginSettings) {
                $config[$handle] = $value->configData();
                $changed = true;
            }
        }

        if ($changed) {
            $repository->set('craft', $config);
        }
    }

    public function boot(): void
    {
        $this->app->make(GeneralConfig::class);

        $this->bootPublishables();
        $this->loadHtmlSanitizers();
    }

    private function loadEnvironmentVariablesWhenConfigIsCached(): void
    {
        if (! $this->app->configurationIsCached()) {
            return;
        }

        $this->app->instance('config_loaded_from_cache', false);

        try {
            new LoadEnvironmentVariables()->bootstrap($this->app);
        } finally {
            $this->app->instance('config_loaded_from_cache', true);
        }
    }

    private function bootPublishables(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        collect($this->configFiles)->each(function (string $file) {
            $source = $file === 'twig-sandbox'
                ? __DIR__.'/../../stubs/twig-sandbox.php.stub'
                : __DIR__."/../../config/$file.php";

            $this->publishes([$source => config_path("craft/$file.php")], 'craftcms-config');
        });
    }

    private function loadGeneralConfig(ConfigRepository $repository): GeneralConfig
    {
        $staticConfig = $repository->get('craft.general', []);

        if ($staticConfig instanceof GeneralConfig) {
            $config = clone $staticConfig;
        } else {
            if (! is_array($staticConfig)) {
                throw new InvalidArgumentException('The [craft.general] configuration must be an array.');
            }

            Typecast::properties(GeneralConfig::class, $staticConfig);
            $config = GeneralConfig::create();

            foreach ($staticConfig as $setting => $value) {
                $this->applyGeneralConfigSetting($config, $setting, $value);
            }
        }

        $this->applyRenamedEnvironmentOverrides($config);

        $envConfig = Env::config($config::class, 'CRAFT_');
        $this->deprecateEnvironmentOverrides($config::class, 'CRAFT_', $envConfig);
        Typecast::properties($config::class, $envConfig);

        foreach ($envConfig as $setting => $value) {
            $this->applyGeneralConfigSetting($config, $setting, $value);
        }

        $this->applyNestedEnvironmentOverrides($config);

        foreach ($config->aliases as $name => $value) {
            if ($value === null) {
                Aliases::remove($name);
            } else {
                Aliases::set($name, $value);
            }
        }

        $repository->set('craft.general', $config);

        return $config;
    }

    /**
     * Applies environment variables named after renamed settings, such as `CRAFT_ENABLE_GQL` for `gql.enabled`.
     * Variables named after the new settings, such as `CRAFT_GQL` and `CRAFT_GQL_ENABLED`, are applied after them, so
     * they win. A variable that's named after both, such as `CRAFT_QUEUE_NAME` for `queueName` and `queue.name`, is
     * left to the new setting.
     */
    private function applyRenamedEnvironmentOverrides(GeneralConfig $config): void
    {
        $cloned = [];

        foreach ($config::renamedSettings() as $setting => $newSetting) {
            $envName = 'CRAFT_'.$this->renamedSettingEnvName($config, $setting, $newSetting);

            if ($envName === 'CRAFT_'.str($newSetting)->replace('.', '_')->snake()->upper()->value()) {
                continue;
            }

            $value = Env::get($envName);

            if ($value === null) {
                continue;
            }

            // A cached config may share its nested objects with another copy, so change a clone
            $nested = str($newSetting)->before('.')->value();

            if ($nested !== $newSetting && ! isset($cloned[$nested])) {
                $config->{$nested} = clone $config->{$nested};
                $cloned[$nested] = true;
            }

            $this->deprecateEnvironmentOverride($envName, $setting);
            $config->{$setting} = $value;
        }
    }

    /**
     * Returns the environment variable name a renamed setting had, without the `CRAFT_` prefix. A setting that kept its
     * name and has an `#[EnvName]` attribute, such as `disable2fa`, keeps the variable name it had, `DISABLE_2FA`.
     */
    private function renamedSettingEnvName(GeneralConfig $config, string $setting, string $newSetting): string
    {
        [$nested, $property] = array_pad(explode('.', $newSetting, 2), 2, null);

        if ($property === $setting) {
            $attribute = Arr::first(new ReflectionProperty($config->{$nested}, $property)->getAttributes(EnvName::class));

            if ($attribute) {
                return $attribute->newInstance()->name;
            }
        }

        return str($setting)->snake()->upper()->value();
    }

    /**
     * Applies `CRAFT_{SETTING}_{NESTED_SETTING}` environment variables to settings that hold config objects, such as
     * `CRAFT_MCP_INSTRUCTIONS` for `mcp.instructions`. Craft applies them after a whole-object variable such as
     * `CRAFT_MCP`, so they override it.
     */
    private function applyNestedEnvironmentOverrides(GeneralConfig $config): void
    {
        foreach (get_object_vars($config) as $setting => $nested) {
            if (! $nested instanceof BaseConfig) {
                continue;
            }

            $prefix = 'CRAFT_'.str($setting)->snake()->upper()->value().'_';
            $envConfig = Env::config($nested::class, $prefix);

            if ($envConfig === []) {
                continue;
            }

            $this->deprecateEnvironmentOverrides($nested::class, $prefix, $envConfig, "$setting.");

            Typecast::properties($nested::class, $envConfig);

            // A cached config may share its nested objects with another copy, so change a clone
            $nested = clone $nested;

            foreach ($envConfig as $property => $value) {
                if (method_exists($nested, $property)) {
                    $nested->{$property}($value);
                } else {
                    $nested->{$property} = $value;
                }
            }

            $config->{$setting} = $nested;
        }
    }

    /**
     * Logs a deprecation warning for each `CRAFT_` environment variable that overrides a general config setting.
     *
     * @param  class-string<BaseConfig>  $class
     * @param  array<string, mixed>  $settings  The settings the variables set, from [[Env::config()]]
     */
    private function deprecateEnvironmentOverrides(string $class, string $prefix, #[\SensitiveParameter] array $settings, string $settingPrefix = ''): void
    {
        foreach (array_keys($settings) as $setting) {
            $attribute = Arr::first(new ReflectionProperty($class, $setting)->getAttributes(EnvName::class));
            $var = $prefix.($attribute?->newInstance()->name ?? str($setting)->snake()->upper()->value());

            $this->deprecateEnvironmentOverride($var, $settingPrefix.$setting);
        }
    }

    private function deprecateEnvironmentOverride(string $var, string $setting): void
    {
        Deprecator::log("env:$var", "Setting `$setting` with the `$var` environment variable is deprecated. Set it in `config/craft/general.php` instead, using an environment variable that doesn’t start with `CRAFT_`, such as `->allowAdminChanges(env('ALLOW_ADMIN_CHANGES'))`.");
    }

    private function applyGeneralConfigSetting(GeneralConfig $config, string $setting, mixed $value): void
    {
        if (method_exists($config, $setting)) {
            $config->{$setting}($value);

            return;
        }

        $config->{$setting} = $value;
    }

    private function loadHtmlSanitizers(): void
    {
        $sanitizers = $this->app->make(HtmlSanitizerManager::class);
        $definitions = $this->app->make(ConfigRepository::class)->get('craft.sanitizers', []);

        if (! is_array($definitions)) {
            throw new InvalidArgumentException('The [craft.sanitizers] configuration must be an array.');
        }

        foreach ($definitions as $name => $definition) {
            if (! is_string($name) || ! is_array($definition)) {
                throw new InvalidArgumentException('HTML sanitizer configuration definitions must be named arrays.');
            }

            $sanitizers->extend($name, $definition);
        }
    }
}
