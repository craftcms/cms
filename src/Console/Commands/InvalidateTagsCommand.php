<?php

declare(strict_types=1);

namespace CraftCms\Cms\Console\Commands;

use CraftCms\Cms\Console\CraftCommand;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Utility\Utilities\ClearCaches;
use CraftCms\DependencyAwareCache\Dependency\TagDependency;
use Illuminate\Console\Command;

use function CraftCms\Cms\t;
use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\table;

class InvalidateTagsCommand extends Command
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
        if ($this->signature === 'craft:invalidate-tags') {
            return $this->list();
        }

        $tag = Str::after($this->signature, 'craft:invalidate-tags:');

        if ($tag === 'all') {
            collect(ClearCaches::tagOptions())
                ->flatMap(fn (array $option) => array_values(Arr::only($option, ['tag'])))
                ->each(function (string $tag) {
                    $this->call("craft:invalidate-tags:$tag");
                });

            return self::SUCCESS;
        }

        $option = collect(ClearCaches::tagOptions())->firstWhere('tag', $tag);

        $this->components->task(
            t('Invalidating:').' '.$option['label'],
            function () use ($option) {
                TagDependency::invalidate($option['tag']);
            }
        );

        return self::SUCCESS;
    }

    public function list(): int
    {
        $this->components->info('The following tags can be invalidated:');

        table(
            headers: ['Tag', 'Label'],
            rows: collect(ClearCaches::tagOptions())
                ->map(fn (array $option) => Arr::only($option, ['tag', 'label']))
                ->all(),
        );

        if (! $this->input->isInteractive()) {
            return self::SUCCESS;
        }

        $tags = multiselect(
            label: 'Which caches do you want to clear?',
            options: collect(ClearCaches::tagOptions())
                ->mapWithKeys(fn (array $option) => [$option['tag'] => $option['label']])
                ->all(),
        );

        foreach ($tags as $tag) {
            $this->call("craft:invalidate-tags:$tag");
        }

        return self::SUCCESS;
    }

    public static function signatures(): array
    {
        $signatures = [
            [
                'signature' => 'craft:invalidate-tags',
                'description' => 'Lists the caches that can be cleared.',
            ],
            [
                'signature' => 'craft:invalidate-tags:all',
                'description' => 'Clear all caches.',
                'aliases' => ['invalidate-tags/all'],
            ],
        ];

        foreach (ClearCaches::tagOptions() as $option) {
            $signatures[] = [
                'signature' => 'craft:invalidate-tags:'.$option['tag'],
                'description' => $option['label'],
                'aliases' => ['invalidate-tags/'.$option['tag']],
            ];
        }

        return $signatures;
    }
}
