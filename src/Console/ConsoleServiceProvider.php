<?php

declare(strict_types=1);

namespace CraftCms\Cms\Console;

use CraftCms\Cms\Console\Commands\ClearCachesCommand;
use CraftCms\Cms\Console\Commands\Env\EnvRemoveCommand;
use CraftCms\Cms\Console\Commands\Env\EnvSetCommand;
use CraftCms\Cms\Console\Commands\Env\EnvShowCommand;
use CraftCms\Cms\Console\Commands\IdeHelper\GenerateCustomFieldsCommand;
use CraftCms\Cms\Console\Commands\Install\InstallCheckCommand;
use CraftCms\Cms\Console\Commands\Install\InstallCommand;
use CraftCms\Cms\Console\Commands\InvalidateTagsCommand;
use CraftCms\Cms\Console\Commands\Setup\CloudCommand;
use CraftCms\Cms\Console\Commands\Setup\DatabaseCredentialsCommand;
use CraftCms\Cms\Console\Commands\Setup\PublishCommand;
use CraftCms\Cms\Console\Commands\Setup\SetupCommand;
use CraftCms\Cms\Console\Commands\Setup\WelcomeCommand;
use CraftCms\Cms\Console\Commands\Twig\TwigCacheCommand;
use CraftCms\Cms\Console\Commands\Twig\TwigClearCommand;
use CraftCms\Cms\Console\Commands\UpCommand;
use CraftCms\Cms\Console\Commands\Utils\AsciiFilenamesCommand;
use CraftCms\Cms\Console\Commands\Utils\DeleteEmptyVolumeFoldersCommand;
use CraftCms\Cms\Console\Commands\Utils\FixElementUidsCommand;
use CraftCms\Cms\Console\Commands\Utils\FixFieldLayoutUidsCommand;
use CraftCms\Cms\Console\Commands\Utils\PruneOrphanedEntriesCommand;
use CraftCms\Cms\Console\Commands\Utils\PruneProvisionalDraftsCommand;
use CraftCms\Cms\Console\Commands\Utils\PruneRevisionsCommand;
use CraftCms\Cms\Console\Commands\Utils\UpdateUsernamesCommand;
use CraftCms\Cms\GarbageCollection\Commands\RunCommand;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use Illuminate\Support\ServiceProvider;

/**
 * @internal
 */
class ConsoleServiceProvider extends ServiceProvider
{
    /** @var list<class-string> */
    private array $commands = [
        // Install
        UpCommand::class,
        InstallCommand::class,
        InstallCheckCommand::class,

        // Setup
        WelcomeCommand::class,
        DatabaseCredentialsCommand::class,
        SetupCommand::class,
        CloudCommand::class,
        PublishCommand::class,

        // Env
        EnvShowCommand::class,
        EnvSetCommand::class,
        EnvRemoveCommand::class,

        // Gc
        RunCommand::class,

        // IDE Helper
        GenerateCustomFieldsCommand::class,

        // Twig
        TwigCacheCommand::class,
        TwigClearCommand::class,

        // Utils
        AsciiFilenamesCommand::class,
        DeleteEmptyVolumeFoldersCommand::class,
        FixFieldLayoutUidsCommand::class,
        FixElementUidsCommand::class,
        PruneRevisionsCommand::class,
        PruneProvisionalDraftsCommand::class,
        PruneOrphanedEntriesCommand::class,
        UpdateUsernamesCommand::class,
    ];

    public function boot(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->app->terminating(function () {
            $this->app->make(ProjectConfig::class)->flush();
        });

        $this->commands($this->commands);

        $this->app->booted(function () {
            foreach (ClearCachesCommand::signatures() as $signature) {
                $this->commands(new ClearCachesCommand(
                    signature: $signature['signature'],
                    description: $signature['description'],
                    aliases: $signature['aliases'] ?? [],
                ));
            }

            foreach (InvalidateTagsCommand::signatures() as $signature) {
                $this->commands(new InvalidateTagsCommand(
                    signature: $signature['signature'],
                    description: $signature['description'],
                    aliases: $signature['aliases'] ?? [],
                ));
            }
        });

        $this->optimizes(
            optimize: 'craft:twig:cache',
            clear: 'craft:twig:clear',
            key: 'twig'
        );

        $this->publishes([
            __DIR__.'/craft.stub' => base_path('craft'),
        ], ['craftcms', 'craftcms-console']);
    }
}
