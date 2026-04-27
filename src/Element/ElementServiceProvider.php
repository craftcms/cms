<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element;

use CraftCms\Cms\Element\BulkOp\BulkOpDeferrals;
use CraftCms\Cms\Element\BulkOp\Events\AfterBulkOp;
use CraftCms\Cms\Element\Commands\DeleteAllOfTypeCommand;
use CraftCms\Cms\Element\Commands\DeleteCommand;
use CraftCms\Cms\Element\Commands\Resave\ResaveAddressesCommand;
use CraftCms\Cms\Element\Commands\Resave\ResaveAllCommand;
use CraftCms\Cms\Element\Commands\Resave\ResaveAssetsCommand;
use CraftCms\Cms\Element\Commands\Resave\ResaveEntriesCommand;
use CraftCms\Cms\Element\Commands\Resave\ResaveUsersCommand;
use CraftCms\Cms\Element\Commands\RestoreCommand;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class ElementServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Event::listen(AfterBulkOp::class, fn (AfterBulkOp $event) => app(BulkOpDeferrals::class)->replay($event->key));

        app()->terminating(fn () => app(BulkOpDeferrals::class)->persistPending());

        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->commands([
            DeleteCommand::class,
            DeleteAllOfTypeCommand::class,
            RestoreCommand::class,
            ResaveAllCommand::class,
            ResaveEntriesCommand::class,
            ResaveAssetsCommand::class,
            ResaveAddressesCommand::class,
            ResaveUsersCommand::class,
        ]);
    }
}
