<?php

namespace CraftCms\Cms\Console;

use Illuminate\Support\Str;

/**
 * @mixin \Illuminate\Console\Command
 *
 * @phpstan-ignore trait.unused
 */
trait CraftCommand
{
    public function removeCraftGroup(): void
    {
        if (empty($this->signature)) {
            $this->signature = $this->name;
        }

        $this->signature = Str::after($this->signature, 'craft:');

        parent::__construct();
    }
}
