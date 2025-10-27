<?php

declare(strict_types=1);

namespace CraftCms\Cms\Console;

use Composer\InstalledVersions;
use CraftCms\Cms\Support\Str;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;
use Symfony\Component\Console\Command\Command as SymfonyCommand;

/**
 * @internal
 */
final class Application extends \Illuminate\Console\Application
{
    public function __construct(Container $laravel, Dispatcher $events, $version)
    {
        parent::__construct($laravel, $events, $version);

        $this->setName('Craft CMS');
        $this->setVersion(InstalledVersions::getPrettyVersion('craftcms/cms'));
    }

    /**
     * This makes sure any Craft commands with a conflicting
     * name get run before any Laravel native commands
     * when running through `php craft`.
     */
    #[\Override]
    public function find(string $name): SymfonyCommand
    {
        if ($this->has("craft:{$name}")) {
            return parent::find("craft:{$name}");
        }

        return parent::find($name);
    }

    /**
     * This cleans up the `php craft` output which strips
     * all the command names of their `craft:` prefix
     * and sets any non-CraftCommand to hidden.
     */
    #[\Override]
    public function all(?string $namespace = null): array
    {
        return collect(parent::all($namespace))
            ->mapWithKeys(function (SymfonyCommand $command, string $name) {
                // Allow some default commands to show up
                if (in_array($command->getName(), [
                    'list',
                    'help',
                    'make:cache-table',
                    'make:sessions-table',
                    'vendor:publish',
                ])) {
                    return [$name => $command];
                }

                if (! in_array(CraftCommand::class, class_uses($command))) {
                    $command->setHidden();

                    return [$name => $command];
                }

                $command->setName(Str::after($command->getName(), 'craft:'));

                return [Str::after($command->getName(), 'craft:') => $command];
            })
            ->all();
    }
}
