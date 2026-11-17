<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Console;

use CraftCms\Cms\Console\TerminalCommands;
use Illuminate\Console\Command;
use Override;

class OnCommand extends Command
{
    #[Override]
    protected $signature = 'craft:on';

    #[Override]
    protected $description = 'Takes the system online.';

    public function handle(): int
    {
        $this->components->warn('The `craft:on` command is deprecated. Use `' . app(TerminalCommands::class)->artisan('up') . '` instead.');

        return $this->call('up');
    }
}
