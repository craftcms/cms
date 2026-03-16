<?php

declare(strict_types=1);

namespace CraftCms\Cms\Updates\Commands;

use CraftCms\Cms\Console\CraftCommand;
use CraftCms\Cms\Support\Composer;
use Illuminate\Console\Command;
use Throwable;

use function Laravel\Prompts\spin;

class ComposerInstallCommand extends Command
{
    use CraftCommand;

    #[\Override]
    protected $signature = 'craft:update:composer-install';

    #[\Override]
    protected $description = 'Installs Composer';

    #[\Override]
    protected $aliases = ['update/composer-install'];

    public function handle(Composer $composer): int
    {
        $output = '';

        try {
            spin(function () use ($composer, &$output) {
                $composer->install(callback: function ($type, $buffer) use (&$output) {
                    $output .= $buffer;
                });

                $this->callSilent('package:discover');
            }, 'Performing composer install');

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error($output);

            throw $e;
        }
    }
}
