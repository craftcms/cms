<?php

declare(strict_types=1);

namespace CraftCms\Cms\Console\Commands\Env;

use CraftCms\Cms\Console\CraftCommand;
use CraftCms\Cms\Support\Env;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\PromptsForMissingInput;
use Illuminate\Support\Facades\Artisan;

final class EnvShowCommand extends Command implements PromptsForMissingInput
{
    use CraftCommand;

    protected $signature = 'craft:env:show {name}';

    protected $description = 'Displays the value of an environment variable, or sets its value if $name contains `=`.';

    protected $aliases = ['env', 'env/show'];

    public function handle(): void
    {
        $name = $this->argument('name');

        if (str_contains($name, '=')) {
            [$name, $value] = explode('=', $name, 2);

            Artisan::call('env:set', ['name' => $name, 'value' => $value], $this->getOutput());

            return;
        }

        $value = Env::get($name);

        $this->info(var_export($value, true));
    }
}
