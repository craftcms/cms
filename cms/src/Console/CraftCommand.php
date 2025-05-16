<?php

namespace Craft\Cms\Console;

use craft\console\Application;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class CraftCommand extends Command
{
    private Application $app;

    public function __construct(Application $app, string $signature, string $description = '')
    {
        $this->app = $app;
        $this->signature = $signature;
        $this->description = $description;

        parent::__construct();
    }

    public function handle(): void
    {
        $tokens = $this->input->getRawTokens();

        $tokens[0] = str_replace(':', '/', Str::after($tokens[0], 'craft:'));

        $_SERVER['argv'] = array_merge(['craft'], $tokens);

        $exitCode = $this->app->run();
        exit($exitCode);
    }
}
