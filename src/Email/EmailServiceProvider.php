<?php

declare(strict_types=1);

namespace CraftCms\Cms\Email;

use CraftCms\Cms\Email\Commands\SendTestMailCommand;
use Illuminate\Support\ServiceProvider;

class EmailServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->commands([
            SendTestMailCommand::class,
        ]);
    }
}
