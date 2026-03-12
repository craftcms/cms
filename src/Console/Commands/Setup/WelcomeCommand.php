<?php

declare(strict_types=1);

namespace CraftCms\Cms\Console\Commands\Setup;

use CraftCms\Cms\Console\CraftCommand;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;

use function Laravel\Prompts\confirm;

class WelcomeCommand extends Command
{
    use CraftCommand;

    #[\Override]
    protected $signature = 'craft:setup:welcome';

    #[\Override]
    protected $description = 'Called from the `post-create-project-cmd` Composer hook.';

    #[\Override]
    protected $aliases = ['setup/welcome'];

    public function handle(): void
    {
        $craft = <<<EOD

   ______ .______          ___       _______ .___________.
  /      ||   _  \        /   \     |   ____||           |
 |  ,----'|  |_)  |      /  ^  \    |  |__   `---|  |----`
 |  |     |      /      /  /_\  \   |   __|      |  |
 |  `----.|  |\  \----./  _____  \  |  |         |  |
  \______|| _| `._____/__/     \__\ |__|         |__|

     A       N   E   W       I   N   S   T   A   L   L
               ______ .___  ___.      _______.
              /      ||   \/   |     /       |
             |  ,----'|  \  /  |    |   (----`
             |  |     |  |\/|  |     \   \
             |  `----.|  |  |  | .----)   |
              \______||__|  |__| |_______/



EOD;
        $this->components->warn(str_replace("\n", PHP_EOL, $craft));

        if (! Config::get('app.key')) {
            $this->call('key:generate');
        }

        $this->components->info('Welcome to Craft CMS!');

        if (! $this->input->isInteractive() || ! confirm('Are you ready to begin the setup?')) {
            $this->info('Run the following command if you want to setup Craft from your terminal: <fg=cyan>php craft setup</>');

            return;
        }

        $this->call('craft:setup');
    }
}
