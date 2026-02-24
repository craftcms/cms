<?php

declare(strict_types=1);

namespace CraftCms\Cms\Console\Commands\Env;

use CraftCms\Cms\Console\CraftCommand;
use CraftCms\Cms\Support\Env;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\PromptsForMissingInput;
use Illuminate\Foundation\Application;

use function Laravel\Prompts\text;

final class EnvSetCommand extends Command implements PromptsForMissingInput
{
    use CraftCommand;

    #[\Override]
    protected $signature = 'craft:env:set {name} {value?}';

    #[\Override]
    protected $description = 'Sets an environment variable in the `.env` file.';

    #[\Override]
    protected $aliases = ['env/set'];

    public function handle(Application $app): void
    {
        $name = $this->argument('name');

        if (str_contains($name, '=')) {
            [$name, $value] = explode('=', $name, 2);
        } else {
            $value = $this->argument('value');
        }

        if (! $value) {
            $value = text('What is the value?');
        }

        Env::writeVariable(trim($name), trim($value), $app->environmentFilePath(), true);

        $value = trim(var_export($value, true));

        $this->components->info("`{$name}` is now {$value}");
    }
}
