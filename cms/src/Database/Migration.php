<?php

namespace CraftCms\Cms\Database;

use Closure;
use Illuminate\Console\View\Components\Task;
use Illuminate\Database\Migrations\Migration as LaravelMigration;
use Laravel\Prompts\Output\ConsoleOutput;

/** @since 6.0.0 */
abstract class Migration extends LaravelMigration
{
    protected ConsoleOutput $output;

    public function __construct()
    {
        $this->output = new ConsoleOutput;
    }

    protected function task(string $message, Closure $callable): void
    {
        /** @phpstan-ignore-next-line */
        new Task($this->output)->render($message, $callable);
    }
}
