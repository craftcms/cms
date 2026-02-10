<?php

declare(strict_types=1);

namespace CraftCms\Cms\Console\Commands\IdeHelper;

use CraftCms\Cms\Field\IdeHelper\CustomFieldIdeHelperGenerator;
use Illuminate\Console\Command;

use function CraftCms\Cms\t;

final class GenerateCustomFieldsCommand extends Command
{
    protected $signature = 'craft:ide-helper:custom-fields';

    protected $description = 'Generate IDE helper file for custom fields';

    public function handle(CustomFieldIdeHelperGenerator $generator): int
    {
        $this->components->task(
            t('Generating custom field IDE helper'),
            fn () => $generator->generate(),
        );

        $this->components->info(t('IDE helper generated successfully.'));

        return self::SUCCESS;
    }
}
