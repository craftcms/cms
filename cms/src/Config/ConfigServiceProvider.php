<?php

namespace CraftCms\Cms\Config;

use craft\helpers\App;
use craft\helpers\Typecast;
use CraftCms\Aliases\Facades\Aliases;
use Illuminate\Support\Env;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;
use Throwable;

class ConfigServiceProvider extends ServiceProvider
{
    private array $configFiles = [
        'general',
        'redirects',
        'routes',
    ];

    public function register(): void
    {
        Env::extend(fn () => ConstAdapter::class);

        $this->app->bind(GeneralConfig::class, function () {
            return Config::get('craft.general');
        });
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
            $this->publishes([__DIR__."/../../config/$file.php" => config_path("craft/$file.php")], 'craftcms');
        });
    }

    private function loadGeneralConfig(): void
    {
        $generalConfig = Config::get('craft.general', []);

        /**
         * When the configuration is a simple array config, load it into
         * the GeneralConfig object and replace the configuration key.
         */
        if (! $generalConfig instanceof BaseConfig) {
            $generalConfig = GeneralConfig::__set_state($generalConfig);

            Config::set('craft.general', $generalConfig);
        }

        // Get any environment value overrides
        $envConfig = App::envConfig(GeneralConfig::class, 'CRAFT_');

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
