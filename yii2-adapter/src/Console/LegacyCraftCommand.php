<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Console;

use craft\console\Application;
use CraftCms\Cms\Console\CraftCommand;
use CraftCms\Cms\Deprecator\Deprecator;
use CraftCms\Cms\Support\Str;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\StringInput;

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

    public function handle(Deprecator $deprecator): int
    {
        $tokens = $this->tokens($this->input);

        $tokens[0] = str_replace(':', '/', Str::after($tokens[0], 'craft:'));

        if ($this->deprecationMessage) {
            $deprecator->log(__METHOD__, $this->deprecationMessage);
        }

        $argv = $_SERVER['argv'] ?? null;
        $_SERVER['argv'] = array_merge(['craft'], $tokens);

        try {
            return $this->app->run();
        } finally {
            if ($argv === null) {
                unset($_SERVER['argv']);
            } else {
                $_SERVER['argv'] = $argv;
            }
        }
    }

    /** @return list<string> */
    private function tokens(InputInterface $input): array
    {
        if ($input instanceof ArgvInput) {
            return $input->getRawTokens();
        }

        return new StringInput($this->getName() . ' ' . (string) $input)->getRawTokens();
    }
}
