<?php

declare(strict_types=1);

namespace CraftCms\Cms\Config;

use CraftCms\Aliases\Aliases;
use CraftCms\Cms\Support\Env;
use CraftCms\Cms\Support\HtmlSanitizer\HtmlSanitizerManager;
use CraftCms\Cms\Support\Typecast;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;
use Override;

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

        $this->app->singleton(GeneralConfig::class, function () {
            $repository = $this->app->make(ConfigRepository::class);

            return $this->loadGeneralConfig($repository);
        });

        collect($this->configFiles)->each(function (string $file) {
            if ($file === 'general') {
                return;
            }

            $this->mergeConfigFrom(__DIR__."/../../config/$file.php", "craft.$file");
        });
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
            $this->publishes([__DIR__."/../../config/$file.php" => config_path("craft/$file.php")], 'craftcms-config');
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

        $envConfig = Env::config($config::class, 'CRAFT_');
        Typecast::properties($config::class, $envConfig);

        foreach ($envConfig as $setting => $value) {
            $this->applyGeneralConfigSetting($config, $setting, $value);
        }

        foreach ($config->aliases as $name => $value) {
            Aliases::set($name, $value);
        }

        $repository->set('craft.general', $config);

        return $config;
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
