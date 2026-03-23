<?php

declare(strict_types=1);

namespace CraftCms\Cms\Console\Commands\Setup;

use CraftCms\Cms\Cms;
use CraftCms\Cms\Console\CraftCommand;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Override;

use function Laravel\Prompts\confirm;

class WelcomeCommand extends Command
{
    use CraftCommand;

    #[Override]
    protected $signature = 'craft:setup:welcome';

    #[Override]
    protected $description = 'Called from the `post-create-project-cmd` Composer hook.';

    #[Override]
    protected $aliases = ['setup/welcome'];

    public function handle(): void
    {
        $this->renderBanner();

        if (! Config::get('app.key')) {
            $this->call('key:generate');
        }

        if (! $this->input->isInteractive() || ! confirm('Are you ready to begin the setup?')) {
            $this->info('Run the following command if you want to setup Craft from your terminal: <fg=cyan>php craft setup</>');

            return;
        }

        $this->call('craft:setup');
    }

    private function renderBanner(): void
    {
        $version = Cms::VERSION;

        $lines = [
            '  ██████╗██████╗  █████╗ ███████╗████████╗     ██████╗███╗   ███╗███████╗',
            ' ██╔════╝██╔══██╗██╔══██╗██╔════╝╚══██╔══╝    ██╔════╝████╗ ████║██╔════╝',
            ' ██║     ██████╔╝███████║█████╗     ██║       ██║     ██╔████╔██║███████╗',
            ' ██║     ██╔══██╗██╔══██║██╔══╝     ██║       ██║     ██║╚██╔╝██║╚════██║',
            ' ╚██████╗██║  ██║██║  ██║██║        ██║       ╚██████╗██║ ╚═╝ ██║███████║',
            '  ╚═════╝╚═╝  ╚═╝╚═╝  ╚═╝╚═╝        ╚═╝        ╚═════╝╚═╝     ╚═╝╚══════╝',
        ];

        $gradient = [
            [176, 46, 31],
            [197, 53, 35],
            [216, 60, 39],
            [229, 66, 43],
            [239, 83, 58],
            [248, 101, 74],
        ];

        echo PHP_EOL;

        foreach ($lines as $i => $line) {
            [$red, $green, $blue] = $gradient[$i];

            echo "\e[38;2;{$red};{$green};{$blue}m{$line}\e[0m".PHP_EOL;
        }

        echo PHP_EOL;

        $tagline = ' ✦ A new install ✦ ';
        [$red, $green, $blue] = $gradient[3];
        echo "\e[48;2;{$red};{$green};{$blue}m\e[30m\e[1m{$tagline}\e[0m  \e[38;5;245mv{$version}\e[0m".PHP_EOL;

        echo PHP_EOL;
    }
}
