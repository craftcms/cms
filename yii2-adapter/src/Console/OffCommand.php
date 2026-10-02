<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Console;

use Illuminate\Console\Command;
use Override;

class OffCommand extends Command
{
    #[Override]
    protected $signature = 'craft:off
        {--retry= : Number of seconds the Retry-After header should be set to for 503 responses.}
    ';

    #[Override]
    protected $description = 'Takes the system offline.';

    public function handle(): int
    {
        $this->components->warn('The `craft off` command is deprecated. Use `down` instead.');

        $retry = $this->option('retry');

        return $this->call('down', $retry === null ? [] : ['--retry' => $retry]);
    }
}
