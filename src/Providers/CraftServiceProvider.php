<?php

declare(strict_types=1);

namespace CraftCms\Cms\Providers;

use CraftCms\Cms\Asset\AssetServiceProvider;
use CraftCms\Cms\Config\ConfigServiceProvider;
use CraftCms\Cms\Console\ConsoleServiceProvider;
use CraftCms\Cms\Database\DatabaseServiceProvider;
use CraftCms\Cms\Deprecator\DeprecatorServiceProvider;
use CraftCms\Cms\Field\FieldsServiceProvider;
use CraftCms\Cms\License\LicenseServiceProvider;
use CraftCms\Cms\Plugin\PluginServiceProvider;
use CraftCms\Cms\ProjectConfig\ProjectConfigServiceProvider;
use CraftCms\Cms\Section\SectionServiceProvider;
use CraftCms\Cms\Translation\TranslationServiceProvider;
use CraftCms\Cms\Twig\TwigServiceProvider;
use CraftCms\Cms\Updates\UpdatesServiceProvider;
use CraftCms\Cms\User\UserServiceProvider;
use Illuminate\Support\AggregateServiceProvider;

final class CraftServiceProvider extends AggregateServiceProvider
{
    protected $providers = [
        ConfigServiceProvider::class,
        FilesystemServiceProvider::class,
        TranslationServiceProvider::class,
        DatabaseServiceProvider::class,
        TwigServiceProvider::class,
        ProjectConfigServiceProvider::class,
        DeprecatorServiceProvider::class,
        LicenseServiceProvider::class,
        AppServiceProvider::class,
        IconServiceProvider::class,
        ConsoleServiceProvider::class,
        PluginServiceProvider::class,
        AssetServiceProvider::class,
        UpdatesServiceProvider::class,
        UserServiceProvider::class,
        FieldsServiceProvider::class,
        SectionServiceProvider::class,
    ];
}
