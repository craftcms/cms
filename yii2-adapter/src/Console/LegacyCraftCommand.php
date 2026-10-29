<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Console;

use craft\console\Application;
use CraftCms\Cms\Console\CraftCommand;
use CraftCms\Cms\Deprecator\Deprecator;
use CraftCms\Cms\Support\Str;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\StringInput;

class LegacyCraftCommand extends Command
{
    use CraftCommand;

    private Application $app;

    /**
     * @param list<string> $legacyOptions The Yii command’s option names.
     */
    public function __construct(
        Application $app,
        string $signature,
        string $description = '',
        bool $hidden = false,
        private readonly ?string $deprecationMessage = null,
        private readonly array $legacyOptions = [],
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

        $tokens = [
            str_replace(':', '/', Str::after($tokens[0], 'craft:')),
            ...$this->legacyOptionTokens(array_slice($tokens, 1)),
        ];

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

    /**
     * Maps Artisan's global options to their Yii controller equivalents, and drops
     * the ones Yii has no equivalent for unless the legacy command defines them.
     *
     * @param list<string> $tokens
     * @return list<string>
     */
    private function legacyOptionTokens(array $tokens): array
    {
        $definition = $this->getApplication()?->getDefinition() ?? new InputDefinition();
        $legacyTokens = [];

        while (($token = array_shift($tokens)) !== null) {
            if ($token === '--') {
                array_push($legacyTokens, $token, ...$tokens);
                break;
            }

            if (str_starts_with($token, '--')) {
                $name = Str::before(substr($token, 2), '=');
            } elseif (str_starts_with($token, '-') && $definition->hasShortcut(substr($token, 1, 1))) {
                $name = $definition->getOptionForShortcut(substr($token, 1, 1))->getName();
            } else {
                $legacyTokens[] = $token;
                continue;
            }

            if (in_array($name, $this->legacyOptions, true)) {
                $legacyTokens[] = str_starts_with($token, '--') ? $token : "--$name";
                continue;
            }

            if ($name === 'no-interaction') {
                $legacyTokens[] = '--interactive=0';
                continue;
            }

            if ($name === 'ansi' || $name === 'no-ansi') {
                $legacyTokens[] = $name === 'ansi' ? '--color=1' : '--color=0';
                continue;
            }

            if (!$definition->hasOption($name)) {
                $legacyTokens[] = $token;
                continue;
            }

            if (
                $definition->getOption($name)->acceptValue() &&
                !str_contains($token, '=') &&
                isset($tokens[0]) &&
                !str_starts_with($tokens[0], '-')
            ) {
                array_shift($tokens);
            }
        }

        return $legacyTokens;
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
