<?php

declare(strict_types=1);

namespace CraftCms\Cms\Console\Commands;

use Override;

class ListCachesCommand extends ClearCachesCommand
{
    #[Override]
    protected $signature = 'craft:clear-caches {keys?*}';

    public function __construct()
    {
        parent::__construct($this->signature, 'Lists available caches to clear or clear specific caches.');
    }

    #[Override]
    public function handle(): int
    {
        $keys = $this->argument('keys');

        if ($keys !== []) {
            foreach ($keys as $key) {
                $this->call("craft:clear-caches:$key");
            }

            return self::SUCCESS;
        }

        return $this->list();
    }
}
