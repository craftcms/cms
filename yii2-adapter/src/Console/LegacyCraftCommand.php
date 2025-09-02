<?php

namespace CraftCms\Yii2Adapter\Console;

use craft\console\Application;
use CraftCms\Cms\Console\CraftCommand;
use CraftCms\Cms\Deprecator\Deprecator;
use CraftCms\Cms\Support\Str;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\ArgvInput;

class LegacyCraftCommand extends Command
{
    use CraftCommand;

    private Application $app;

    public function __construct(
        Application $app,
        string $signature,
        string $description = '',
        bool $hidden = false,
        private readonly ?string $deprecationMessage = null,
    ) {
        $this->app = $app;
        $this->signature = $signature;
        $this->description = $description;
        $this->hidden = $hidden;

        parent::__construct();
    }

    public function handle(Deprecator $deprecator): never
    {
        assert($this->input instanceof ArgvInput);

        $tokens = $this->input->getRawTokens();

        $tokens[0] = str_replace(':', '/', Str::after($tokens[0], 'craft:'));

        if ($this->deprecationMessage) {
            $deprecator->log(__METHOD__, $this->deprecationMessage);
        }

        $_SERVER['argv'] = array_merge(['craft'], $tokens);

        $exitCode = $this->app->run();

        exit($exitCode);
    }
}
