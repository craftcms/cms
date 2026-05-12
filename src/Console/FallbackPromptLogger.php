<?php

declare(strict_types=1);

namespace CraftCms\Cms\Console;

use Laravel\Prompts\Support\Logger;
use Symfony\Component\Console\Output\OutputInterface;

class FallbackPromptLogger extends Logger
{
    public function __construct(private readonly OutputInterface $output)
    {
        parent::__construct('');
    }

    #[\Override]
    protected function write(string $message, ?string $type = null): void
    {
        match ($type) {
            'success' => $this->output->writeln("  <info>DONE</info> $message"),
            'warning' => $this->output->writeln("  <comment>WARN</comment> $message"),
            'error' => $this->output->writeln("  <error>FAIL</error> $message"),
            'label', 'sublabel' => $this->output->writeln("  $message"),
            'partial' => $this->output->write($message),
            'commitpartial' => $this->output->writeln(''),
            default => $this->output->writeln("  $message"),
        };
    }
}
