<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Console;

use CraftCms\Cms\Console\CraftCommand;
use CraftCms\Cms\Console\TerminalCommands;
use CraftCms\Yii2Adapter\LooseEnds\LooseEndsStore;
use Illuminate\Console\Command;

/**
 * Drops the Yii2 Loose Ends utility’s table, before the adapter is uninstalled.
 */
class AdapterCleanupCommand extends Command
{
    use CraftCommand;

    protected $signature = 'craft:yii2-adapter:cleanup';

    protected $description = 'Removes the Yii2 Loose Ends utility’s data, before the Yii2 adapter is uninstalled';

    public function handle(LooseEndsStore $store): int
    {
        $store->drop();
        $this->components->info('Removed the Yii2 Loose Ends utility’s data. You can now run `' . app(TerminalCommands::class)->composer('remove craftcms/yii2-adapter') . '`.');

        return self::SUCCESS;
    }
}
