<?php

declare(strict_types=1);

namespace CraftCms\Cms\Plugin\Commands;

use Closure;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

use function Laravel\Prompts\suggest;

/**
 * @mixin Command
 */
trait PromptsForMissingHandle
{
    protected function getHandle(?Closure $filter = null): string|false
    {
        $handle = $this->argument('handle');

        if ($handle) {
            return $handle;
        }

        if (! $this->input->isInteractive()) {
            $this->components->error('You must specify a plugin handle.');

            return false;
        }

        return suggest(
            label: 'What is the plugin’s handle?',
            options: $this->plugins->getAllPluginInfo()
                ->when(! is_null($filter), fn (Collection $collection) => $collection->filter($filter))
                ->map(fn (array $info, string $handle) => "{$handle}: {$info['name']} {$info['version']}")
                ->values()
                ->all(),
            validate: fn ($value) => empty($value) ? 'The handle is required.' : null,
            transform: fn ($value) => str_contains((string) $value, ':') ? explode(':', (string) $value)[0] : $value,
        );
    }
}
