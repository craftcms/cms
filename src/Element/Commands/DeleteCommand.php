<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Commands;

use Craft;
use CraftCms\Cms\Console\CraftCommand;
use CraftCms\Cms\Element\Commands\Concerns\ResolvesElementById;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Section\Enums\SectionType;
use Illuminate\Console\Command;
use Illuminate\Console\View\TaskResult;
use Override;

use function Laravel\Prompts\confirm;

final class DeleteCommand extends Command
{
    use CraftCommand;
    use ResolvesElementById;

    #[Override]
    protected $signature = 'craft:elements:delete
        {id : The element ID to delete.}
        {--hard : Hard-delete the element immediately.}
    ';

    #[Override]
    protected $description = 'Deletes an element by its ID.';

    #[Override]
    protected $aliases = ['elements/delete'];

    public function handle(): int
    {
        $element = $this->resolveElementById((int) $this->argument('id'));

        if (is_int($element)) {
            return $element;
        }

        if ($element instanceof Entry && $element->getSection()->type === SectionType::Single) {
            $this->components->error('Deleting single section entries is not allowed.');

            return self::FAILURE;
        }

        if (! $this->option('hard') && $element->dateDeleted) {
            $this->components->error("{$element->getUiLabel()} is already soft-deleted. Try again with --hard to hard-delete it.");

            return self::FAILURE;
        }

        if ($this->input->isInteractive() && ! confirm(sprintf(
            'Are you sure you want to %s "%s"?',
            $this->option('hard') ? 'hard-delete' : 'delete',
            $element->getUiLabel(),
        ))) {
            $this->components->info('Aborted.');

            return self::SUCCESS;
        }

        $failed = false;

        $this->components->task(
            sprintf('Deleting "%s"', $element->getUiLabel()),
            function () use ($element, &$failed): TaskResult {
                $failed = ! Craft::$app->getElements()->deleteElement($element, (bool) $this->option('hard'));

                if ($failed) {
                    return TaskResult::Failure;
                }

                return TaskResult::Success;
            },
        );

        if ($failed) {
            $this->components->error('Could not delete the element.');

            return self::FAILURE;
        }

        $this->components->success('Element deleted.');

        return self::SUCCESS;
    }
}
