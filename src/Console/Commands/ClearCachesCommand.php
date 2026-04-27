<?php

declare(strict_types=1);

namespace CraftCms\Cms\Console\Commands;

use CraftCms\Cms\Console\CraftCommand;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\File;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Utility\Utilities\ClearCaches;
use Illuminate\Console\Command;

use function CraftCms\Cms\t;
use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\table;

class ClearCachesCommand extends Command
{
    use CraftCommand;

    public function __construct(string $signature, string $description, array $aliases = [])
    {
        $this->signature = $signature;
        $this->description = $description;
        $this->aliases = $aliases;

        parent::__construct();
    }

    public function handle(): int
    {
        if ($this->signature === 'craft:clear-caches {keys?*}') {
            /** @phpstan-ignore-next-line */
            if (! empty($keys = $this->argument('keys'))) {
                foreach ($keys as $key) {
                    $this->call("craft:clear-caches:$key");
                }

                return self::SUCCESS;
            }

            return $this->list();
        }

        $key = Str::after($this->signature, 'craft:clear-caches:');

        if ($key === 'all') {
            collect(ClearCaches::cacheOptions())
                ->flatMap(fn (array $option) => array_values(Arr::only($option, ['key'])))
                ->each(function (string $key) {
                    $this->call("craft:clear-caches:$key");
                });

            return self::SUCCESS;
        }

        $option = collect(ClearCaches::cacheOptions())->firstWhere('key', $key);

        $this->components->task(
            t('Clearing:').' '.$option['label'],
            function () use ($option) {
                if (is_string($option['action'])) {
                    File::cleanDirectory($option['action']);

                    return;
                }

                if (isset($option['params'])) {
                    call_user_func_array($option['action'], $option['params']);

                    return;
                }

                $action = $option['action'];
                $action();
            }
        );

        return self::SUCCESS;
    }

    public function list(): int
    {
        $this->components->info('The following caches can be cleared:');

        table(
            headers: ['Key', 'Label', 'Info'],
            rows: collect(ClearCaches::cacheOptions())
                ->map(function (array $option) {
                    $data = Arr::only($option, ['key', 'label', 'info']);

                    if (isset($option['info'])) {
                        $data['label'] = sprintf('%s (%s)', $data['label'], $option['info']);
                    }

                    return $data;
                })
                ->all(),
        );

        if (! $this->input->isInteractive()) {
            return self::SUCCESS;
        }

        $keys = multiselect(
            label: 'Which caches do you want to clear?',
            options: collect(ClearCaches::cacheOptions())
                ->mapWithKeys(fn (array $option) => [$option['key'] => $option['label']])
                ->all(),
        );

        foreach ($keys as $key) {
            $this->call("craft:clear-caches:$key");
        }

        return self::SUCCESS;
    }

    public static function signatures(): array
    {
        $signatures = [
            [
                'signature' => 'craft:clear-caches {keys?*}',
                'description' => 'Lists available caches to clear or clear specific caches.',
            ],
            [
                'signature' => 'craft:clear-caches:all',
                'description' => 'Clears all caches.',
                'aliases' => ['clear-caches/all'],
            ],
        ];

        foreach (ClearCaches::cacheOptions() as $option) {
            $signatures[] = [
                'signature' => 'craft:clear-caches:'.$option['key'],
                'description' => $option['label'],
                'aliases' => ['clear-caches/'.$option['key']],
            ];
        }

        return $signatures;
    }
}
