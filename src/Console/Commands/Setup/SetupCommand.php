<?php

declare(strict_types=1);

namespace CraftCms\Cms\Console\Commands\Setup;

use CraftCms\Cms\Cms;
use CraftCms\Cms\Console\CraftCommand;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;

use function Laravel\Prompts\confirm;

class SetupCommand extends Command
{
    use CraftCommand;

    #[\Override]
    protected $signature = 'craft:setup';

    #[\Override]
    protected $description = 'Sets up all the things. This is an interactive wrapper for the `setup/db-creds`, and `install` commands, each of which support being run non-interactively.';

    public function handle(): int
    {
        if (! Config::get('app.key')) {
            $this->call('key:generate');
        }

        if (! $this->input->isInteractive()) {
            return self::SUCCESS;
        }

        $this->call('craft:setup:db-creds');

        if (Cms::isInstalled(true)) {
            $this->components->warn('It looks like Craft is already installed, so we\'re done here.');

            return self::SUCCESS;
        }

        if (! confirm('Install Craft now?')) {
            $this->components->warn('You can install Craft from a browser once you\'ve set up a web server, or by running this command: <fg=cyan>php craft install</>');

            return self::SUCCESS;
        }

        return $this->call('craft:install');
    }
}
