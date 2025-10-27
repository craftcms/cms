<?php

declare(strict_types=1);

namespace CraftCms\Cms\Database;

use Illuminate\Console\Concerns\InteractsWithIO;
use Illuminate\Console\OutputStyle;
use Illuminate\Console\View\Components\Factory;
use Illuminate\Database\Migrations\Migration as LaravelMigration;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\ConsoleOutput;

abstract class Migration extends LaravelMigration
{
    use InteractsWithIO;

    public function __construct()
    {
        $this->input = new ArrayInput([]);
        $this->output = new OutputStyle($this->input, new ConsoleOutput);
        $this->components = new Factory($this->output);
    }
}
