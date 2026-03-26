<?php

declare(strict_types=1);

namespace CraftCms\Cms\Console\Commands\IdeHelper;

use CraftCms\Cms\Cms;
use CraftCms\Cms\Field\IdeHelper\CustomFieldIdeHelperGenerator;
use Illuminate\Console\Command;

use function CraftCms\Cms\t;

class GenerateCustomFieldsCommand extends Command
{
    #[\Override]
    protected $signature = 'craft:ide-helper:custom-fields';

    #[\Override]
    protected $description = 'Generate IDE helper file for custom fields';

    public function handle(CustomFieldIdeHelperGenerator $generator): int
    {
        if (! Cms::config()->ideHelperEnabled) {
            $this->components->warn(t('IDE helper generation is disabled.'));

            return self::SUCCESS;
        }

        $this->components->task(
            t('Generating custom field IDE helper'),
            function () use ($generator) {
                $generator->generate();
            },
        );

        $this->components->info(t('IDE helper generated successfully.'));

        return self::SUCCESS;
    }
}
