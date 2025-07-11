<?php

namespace Craft\Cms\Console;

use Illuminate\Support\Str;

/**
 * @mixin \Illuminate\Console\Command
 * @phpstan-ignore trait.unused
 */
trait CraftCommand
{
    public function removeCraftGroup(): void
    {
        if (! isset($this->signature)) {
            $this->signature = $this->name;
        }

        $this->signature = Str::after($this->signature, 'craft:');

        parent::__construct();
    }
}
