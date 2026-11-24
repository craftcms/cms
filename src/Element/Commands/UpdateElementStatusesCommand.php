<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Commands;

use CraftCms\Cms\Console\CraftCommand;
use CraftCms\Cms\Element\ElementStatuses;
use CraftCms\Cms\Support\Facades\Deprecator;
use Illuminate\Console\Command;
use Override;

/**
 * Brings elements’ stored statuses up to date with their dates. Craft schedules it every minute.
 *
 * @see ElementStatuses::updateScheduled()
 * @since 6.0.0
 */
final class UpdateElementStatusesCommand extends Command
{
    use CraftCommand;

    #[Override]
    protected $signature = 'craft:update-element-statuses';

    #[Override]
    protected $description = 'Updates the statuses of elements whose post or expiry dates passed, such as entries.';

    /** Deprecated names of the `craft:update-statuses` command this replaces */
    #[Override]
    protected $aliases = ['update-statuses', 'update-statuses:index', 'update-statuses/index'];

    public function handle(ElementStatuses $elementStatuses): int
    {
        if (in_array($this->input->getFirstArgument(), $this->getAliases(), true)) {
            Deprecator::log('craft:update-statuses', 'The `craft:update-statuses` command has been renamed to `craft:update-element-statuses`, which Craft schedules every minute.');
        }

        $count = $elementStatuses->updateScheduled();

        $this->components->info(sprintf('Updated %d %s.', $count, $count === 1 ? 'status' : 'statuses'));

        return self::SUCCESS;
    }
}
