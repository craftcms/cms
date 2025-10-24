<?php

declare(strict_types=1);

namespace CraftCms\Cms\Console\Commands\Env;

use CraftCms\Cms\Console\CraftCommand;
use CraftCms\Cms\Support\Env;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\PromptsForMissingInput;
use Illuminate\Foundation\Application;

final class EnvRemoveCommand extends Command implements PromptsForMissingInput
{
    use CraftCommand;

    protected $signature = 'craft:env:remove {name}';

    protected $description = 'Removes an environment variable from the `.env` file.';

    protected $aliases = ['env/remove'];

    public function handle(Application $app): void
    {
        $name = $this->argument('name');

        Env::removeVariable($name, $app->environmentFilePath());

        $this->info("`{$name}` has been removed.");
    }
}
