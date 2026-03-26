<?php

declare(strict_types=1);

namespace CraftCms\Cms\Providers;

use CraftCms\Cms\Asset\AssetServiceProvider;
use CraftCms\Cms\Auth\AuthServiceProvider;
use CraftCms\Cms\Config\ConfigServiceProvider;
use CraftCms\Cms\Console\ConsoleServiceProvider;
use CraftCms\Cms\Database\DatabaseServiceProvider;
use CraftCms\Cms\Deprecator\DeprecatorServiceProvider;
use CraftCms\Cms\Email\EmailServiceProvider;
use CraftCms\Cms\Entry\EntryServiceProvider;
use CraftCms\Cms\Field\FieldsServiceProvider;
use CraftCms\Cms\FieldLayout\FieldLayoutServiceProvider;
use CraftCms\Cms\Gql\GqlServiceProvider;
use CraftCms\Cms\License\LicenseServiceProvider;
use CraftCms\Cms\Plugin\PluginServiceProvider;
use CraftCms\Cms\ProjectConfig\ProjectConfigServiceProvider;
use CraftCms\Cms\Queue\QueueServiceProvider;
use CraftCms\Cms\Route\RouteServiceProvider;
use CraftCms\Cms\Section\SectionServiceProvider;
use CraftCms\Cms\Structure\StructureServiceProvider;
use CraftCms\Cms\Translation\TranslationServiceProvider;
use CraftCms\Cms\Twig\TwigServiceProvider;
use CraftCms\Cms\Update\UpdatesServiceProvider;
use CraftCms\Cms\User\UserServiceProvider;
use CraftCms\Cms\View\ViewServiceProvider;
use Illuminate\Support\AggregateServiceProvider;
use Inertia\ServiceProvider as InertiaServiceProvider;
use Override;

class CraftServiceProvider extends AggregateServiceProvider
{
    #[Override]
    protected $providers = [
        // InertiaServiceProvider::class,
        ConfigServiceProvider::class,
        AuthServiceProvider::class,
        FilesystemServiceProvider::class,
        TranslationServiceProvider::class,
        DatabaseServiceProvider::class,
        ViewServiceProvider::class,
        TwigServiceProvider::class,
        ProjectConfigServiceProvider::class,
        DeprecatorServiceProvider::class,
        LicenseServiceProvider::class,
        RouteServiceProvider::class,
        AppServiceProvider::class,
        IconServiceProvider::class,
        ConsoleServiceProvider::class,
        EmailServiceProvider::class,
        GqlServiceProvider::class,
        PluginServiceProvider::class,
        AssetServiceProvider::class,
        UpdatesServiceProvider::class,
        UserServiceProvider::class,
        FieldsServiceProvider::class,
        FieldLayoutServiceProvider::class,
        SectionServiceProvider::class,
        EntryServiceProvider::class,
        StructureServiceProvider::class,
        QueueServiceProvider::class,
    ];
}
