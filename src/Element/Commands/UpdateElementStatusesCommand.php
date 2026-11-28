<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Commands;

use CraftCms\Cms\Console\CraftCommand;
use CraftCms\Cms\Element\ElementStatuses;
use DateTimeInterface;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Isolatable;
use Override;

/**
 * Updates the stored statuses of elements whose dates passed. Craft schedules it every minute.
 *
 * @see ElementStatuses::updateScheduled()
 * @since 6.0.0
 */
final class UpdateElementStatusesCommand extends Command implements Isolatable
{
    use CraftCommand;

    #[Override]
    protected $signature = 'craft:update-element-statuses';

    #[Override]
    protected $description = 'Updates the statuses of elements whose post or expiry dates passed, such as entries.';

    #[Override]
    protected $isolated = true;

    public function handle(ElementStatuses $elementStatuses): int
    {
        $count = $elementStatuses->updateScheduled();

        $this->components->info(sprintf('Updated %d %s.', $count, $count === 1 ? 'status' : 'statuses'));

        return self::SUCCESS;
    }

    public function isolationLockExpiresAt(): DateTimeInterface
    {
        return now()->plus(minutes: 15);
    }
}
