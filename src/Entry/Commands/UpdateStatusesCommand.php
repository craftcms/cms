<?php

declare(strict_types=1);

namespace CraftCms\Cms\Entry\Commands;

use CraftCms\Cms\Console\CraftCommand;
use CraftCms\Cms\Element\Commands\UpdateElementStatusesCommand;
use CraftCms\Cms\Support\Facades\Deprecator;
use DateTimeInterface;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Isolatable;
use Override;

/**
 * @since 6.0.0
 * @deprecated in 6.0.0. Use {@see UpdateElementStatusesCommand} instead.
 */
final class UpdateStatusesCommand extends Command implements Isolatable
{
    use CraftCommand;

    #[Override]
    protected $signature = 'craft:update-statuses';

    #[Override]
    protected $description = 'Updates entry statuses. Deprecated in favor of `craft:update-element-statuses`.';

    #[Override]
    protected $aliases = ['update-statuses', 'update-statuses:index', 'update-statuses/index'];

    #[Override]
    protected $isolated = true;

    public function handle(): int
    {
        Deprecator::log('craft:update-statuses', 'The `craft:update-statuses` command has been renamed to `craft:update-element-statuses`, which Craft schedules every minute.');

        return $this->call(UpdateElementStatusesCommand::class);
    }

    public function isolationLockExpiresAt(): DateTimeInterface
    {
        return now()->plus(minutes: 15);
    }
}
