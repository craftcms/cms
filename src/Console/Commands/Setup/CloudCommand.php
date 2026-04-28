<?php

declare(strict_types=1);

namespace CraftCms\Cms\Console\Commands\Setup;

use CraftCms\Cms\Console\CraftCommand;
use CraftCms\Cms\Support\Composer;
use Illuminate\Console\Command;

class CloudCommand extends Command
{
    use CraftCommand;

    #[\Override]
    protected $signature = 'craft:setup:cloud';

    #[\Override]
    protected $description = 'Prepares the Craft install to be deployed to Craft Cloud.';

    #[\Override]
    protected $aliases = ['setup/cloud'];

    public function handle(Composer $composer): int
    {
        if (! $this->input->isInteractive()) {
            $this->error('The setup:cloud command must be run interactively.');

            return self::FAILURE;
        }

        $moduleInstalled = class_exists('craft\cloud\Module');

        $this->components->info(sprintf('%s the `craftcms/cloud` extension …',
            $moduleInstalled ? 'Updating' : 'Installing',
        ));

        $composer->install([
            'craftcms/cloud' => '^3',
        ], function ($type, $buffer) {
            $this->output->write($buffer);
        });

        $this->components->success(sprintf('Extension %s', $moduleInstalled ? 'updated' : 'installed'));

        $this->components->info('Running `cloud:setup` ...');
        $this->call('craft:cloud:setup');

        $this->components->success('The install is now prepared for Craft Cloud');

        return self::SUCCESS;
    }
}
