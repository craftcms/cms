<?php

declare(strict_types=1);

namespace CraftCms\Cms\Plugin\Concerns;

use CraftCms\Cms\Plugin\Plugin;
use Illuminate\Console\Command;

/**
 * @mixin Plugin
 *
 * @internal
 */
trait HasCommands
{
    /**
     * A list of console command classes to register.
     *
     * @var list<class-string<Command>>
     */
    protected array $commands = [];

    public function bootHasCommands(): void
    {
        if (! $this->commands) {
            return;
        }

        $this->commands($this->commands);
    }
}
