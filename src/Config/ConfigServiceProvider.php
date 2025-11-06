<?php

declare(strict_types=1);

namespace CraftCms\Cms\Config;

use CraftCms\Aliases\Aliases;
use CraftCms\Cms\Support\Env;
use CraftCms\Cms\Support\Typecast;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;
use Throwable;

final class ConfigServiceProvider extends ServiceProvider
{
    private array $configFiles = [
        'general',
        'redirects',
        'routes',
    ];

    #[\Override]
    public function register(): void
    {
        Env::extend(fn () => ConstAdapter::class);

        $this->app->singleton(GeneralConfig::class, fn () => $this->app->make(ConfigRepository::class)->get('craft.general'));
    }

    public function boot(): void
    {
        $this->bootPublishables();
        $this->loadGeneralConfig();
    }

    private function bootPublishables(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        collect($this->configFiles)->each(function ($file) {
            $this->publishes([__DIR__."/../../config/$file.php" => config_path("craft/$file.php")], 'craftcms-config');
        });
    }

    private function loadGeneralConfig(): void
    {
        $generalConfig = Config::get('craft.general', []);

        /**
         * When the configuration is a simple array config, load it into
         * the GeneralConfig object and replace the configuration key.
         */
        if (! $generalConfig instanceof GeneralConfig) {
            $generalConfig = GeneralConfig::__set_state($generalConfig);

            Config::set('craft.general', $generalConfig);
        }

        // Get any environment value overrides
        $envConfig = Env::config(GeneralConfig::class, 'CRAFT_');

        Typecast::properties(GeneralConfig::class, $envConfig);

        foreach ($envConfig as $name => $value) {
            // Use the fluent methods when possible, in case it has any value normalization logic
            if (! method_exists($generalConfig, $name)) {
                continue;
            }

            try {
                $generalConfig->$name($value);

                continue;
            } catch (Throwable) {
            }

            $generalConfig->$name = $value;
        }

        foreach ($generalConfig->aliases as $name => $value) {
            Aliases::set($name, $value);
        }
    }
}
