<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Commands;

use CraftCms\Cms\Console\CraftCommand;
use CraftCms\Cms\Element\Commands\Concerns\ResolvesElementById;
use CraftCms\Cms\Element\Elements;
use Illuminate\Console\Command;
use Illuminate\Console\View\TaskResult;
use Override;

final class RestoreCommand extends Command
{
    use CraftCommand;
    use ResolvesElementById;

    #[Override]
    protected $signature = 'craft:elements:restore
        {id : The element ID to restore.}
    ';

    #[Override]
    protected $description = 'Restores a soft-deleted element by its ID.';

    #[Override]
    protected $aliases = ['elements/restore'];

    public function handle(Elements $elements): int
    {
        $element = $this->resolveElementById((int) $this->argument('id'));

        if (is_int($element)) {
            return $element;
        }

        if (! $element->dateDeleted) {
            $this->components->error("{$element->getUiLabel()} is already restored.");

            return self::FAILURE;
        }

        $failed = false;

        $this->components->task(
            sprintf('Restoring "%s"', $element->getUiLabel()),
            function () use ($elements, $element, &$failed): TaskResult {
                $failed = ! $elements->restoreElement($element);

                if ($failed) {
                    return TaskResult::Failure;
                }

                return TaskResult::Success;
            },
        );

        if ($failed) {
            $this->components->error('Could not restore the element.');

            return self::FAILURE;
        }

        $this->components->success('Element restored.');

        return self::SUCCESS;
    }
}
